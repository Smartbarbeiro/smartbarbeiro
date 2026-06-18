<?php

namespace App\Http\Controllers;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\ServicePlanCheckoutService;
use App\Services\ServicePlanSubscriptionSyncService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;

class ServicePlanSubscribeController extends Controller
{
    public function store(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
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

        if (! $mercadoPago->isConfigured()) {
            return back()->withErrors([
                'checkout' => __('messages.payments_not_configured'),
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
            return back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'checkout' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        } catch (\RuntimeException $exception) {
            return back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        }

        return redirect()->away($checkout['checkout_url']);
    }

    public function registerAndStore(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
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
            'password' => Hash::make($validated['password']),
            'is_barbershop' => false,
        ]);

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        if (! $mercadoPago->isConfigured()) {
            try {
                $checkoutService->savePendingSelection(
                    $barbershop,
                    $user,
                    $validated['package_type'],
                    $validated['addon_ids'] ?? [],
                );
            } catch (\InvalidArgumentException $exception) {
                return back()->withErrors([
                    'checkout' => $exception->getMessage(),
                ]);
            }

            return redirect()
                ->route('profile.public', $username)
                ->with('status', 'service-plan-signup-pending');
        }

        try {
            $checkout = $checkoutService->startCheckout(
                $barbershop,
                $user,
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'checkout' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        } catch (\RuntimeException $exception) {
            return back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        }

        return redirect()->away($checkout['checkout_url']);
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

        if ($subscription?->mercadopago_preapproval_id) {
            try {
                $syncService->syncByMercadoPagoId($subscription->mercadopago_preapproval_id);
                $subscription->refresh();
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
