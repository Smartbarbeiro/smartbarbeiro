<?php

namespace App\Http\Controllers;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Rules\UniqueTaxDocument;
use App\Services\ServicePlanCheckoutService;
use App\Services\ServicePlanSubscriptionSyncService;
use App\Services\StripeServicePlanService;
use App\Support\TaxDocument;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ServicePlanSubscribeController extends Controller
{
    public function store(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        StripeServicePlanService $stripe,
    ): RedirectResponse|HttpResponse {
        $barbershop = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $validated = $request->validate([
            'package_type' => ['required', 'in:cut,cut_beard'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer'],
        ]);

        if (! $stripe->isConfigured()) {
            return $this->redirectToServicePlanPayment($username)
                ->withErrors([
                    'checkout' => __('messages.payments_not_configured'),
                ]);
        }

        if (! $stripe->acceptsPaymentsFor($barbershop)) {
            return $this->redirectToServicePlanPayment($username)
                ->withErrors([
                    'checkout' => __('messages.stripe_connect_not_ready'),
                ]);
        }

        try {
            $checkout = $checkoutService->startCheckout(
                $barbershop,
                $request->user(),
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->redirectToServicePlanPayment($username)
                ->withErrors([
                    'checkout' => $exception->getMessage(),
                ]);
        } catch (ApiErrorException $exception) {
            return $this->redirectToServicePlanPayment($username)
                ->withErrors([
                    'checkout' => $stripe->apiExceptionMessage($exception),
                ]);
        } catch (\RuntimeException $exception) {
            return $this->redirectToServicePlanPayment($username)
                ->withErrors([
                    'checkout' => $exception->getMessage(),
                ]);
        }

        return Inertia::location($checkout['checkout_url']);
    }

    public function registerAndStore(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        StripeServicePlanService $stripe,
    ): RedirectResponse|HttpResponse {
        if ($request->user()) {
            return redirect()->route('profile.public', $username);
        }

        $barbershop = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'cpf', new UniqueTaxDocument],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'package_type' => ['required', 'in:cut,cut_beard'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => null,
            'email' => $validated['email'],
            'tax_document' => TaxDocument::normalize($validated['cpf']),
            'password' => Hash::make($validated['password']),
            'is_barbershop' => false,
        ]);

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        if (! $stripe->isConfigured() || ! $stripe->acceptsPaymentsFor($barbershop)) {
            try {
                $checkoutService->savePendingSelection(
                    $barbershop,
                    $user,
                    $validated['package_type'],
                    $validated['addon_ids'] ?? [],
                );
            } catch (\InvalidArgumentException $exception) {
                return $this->redirectToServicePlanPayment($username)
                    ->withErrors([
                        'checkout' => $exception->getMessage(),
                    ]);
            }

            return $this->redirectToServicePlanPayment($username)
                ->with('status', 'service-plan-signup-pending')
                ->withErrors([
                    'checkout' => $stripe->isConfigured()
                        ? __('messages.stripe_connect_not_ready')
                        : __('messages.payments_not_configured'),
                ]);
        }

        try {
            $checkout = $checkoutService->startCheckout(
                $barbershop,
                $user,
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->registeredCheckoutFailure(
                $checkoutService,
                $barbershop,
                $user,
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
                $username,
                $exception->getMessage(),
            );
        } catch (ApiErrorException $exception) {
            return $this->registeredCheckoutFailure(
                $checkoutService,
                $barbershop,
                $user,
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
                $username,
                $stripe->apiExceptionMessage($exception),
            );
        } catch (\RuntimeException $exception) {
            return $this->registeredCheckoutFailure(
                $checkoutService,
                $barbershop,
                $user,
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
                $username,
                $exception->getMessage(),
            );
        }

        return Inertia::location($checkout['checkout_url']);
    }

    private function redirectToServicePlanPayment(string $username): RedirectResponse
    {
        return redirect()->to(route('profile.public', $username).'#pagamento');
    }

    private function registeredCheckoutFailure(
        ServicePlanCheckoutService $checkoutService,
        User $barbershop,
        User $user,
        string $packageType,
        array $addonIds,
        string $username,
        string $message,
    ): RedirectResponse {
        try {
            $checkoutService->savePendingSelection(
                $barbershop,
                $user,
                $packageType,
                $addonIds,
            );
        } catch (\InvalidArgumentException) {
            // Keep the original checkout error below.
        }

        return $this->redirectToServicePlanPayment($username)
            ->withErrors([
                'checkout' => $message,
            ]);
    }

    public function return(
        string $username,
        Request $request,
        ServicePlanSubscriptionSyncService $syncService,
    ): Response {
        $barbershop = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $subscription = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $request->user()->id)
            ->latest()
            ->first();

        if ($request->filled('session_id')) {
            try {
                $syncService->syncByCheckoutSessionId($request->string('session_id')->toString());
                $subscription?->refresh();
            } catch (\Throwable) {
                // Webhook will reconcile; show return page with current status.
            }
        }

        return Inertia::render('Profile/ServicePlanSubscribeReturn', [
            'barbershop' => [
                'name' => $barbershop->name,
                'username' => $barbershop->username,
            ],
            'subscription' => $subscription?->toSummaryArray(),
        ]);
    }
}
