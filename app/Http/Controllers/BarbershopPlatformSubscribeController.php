<?php

namespace App\Http\Controllers;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Services\BarbershopPlatformCheckoutService;
use App\Services\BarbershopPlatformSubscriptionSyncService;
use App\Services\MercadoPagoService;
use App\Services\PaymentEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BarbershopPlatformSubscribeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isBarbershopAccount(), 403);

        if ($user->hasActivePlatformSubscription()) {
            return redirect()->route('dashboard');
        }

        $plan = BarbershopPlatformPlan::current();
        $subscription = $user->platformSubscription;

        return Inertia::render('Platform/Subscribe', [
            'plan' => $plan->toPublicArray(),
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'status_label' => $subscription->statusLabel(),
                'is_active' => $subscription->isActive(),
            ] : null,
            'paymentsConfigured' => app(MercadoPagoService::class)->isConfigured(),
        ]);
    }

    public function store(
        Request $request,
        BarbershopPlatformCheckoutService $checkoutService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse|HttpResponse {
        $user = $request->user();

        abort_unless($user->isBarbershopAccount(), 403);

        if ($user->hasActivePlatformSubscription()) {
            return redirect()->route('dashboard');
        }

        try {
            $result = $checkoutService->startCheckout($user);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['subscribe' => $exception->getMessage()]);
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'subscribe' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        }

        if (! filled($result['checkout_url'])) {
            if (! $mercadoPago->isConfigured()) {
                return back()->with('status', 'platform-subscription-pending');
            }

            return back()->withErrors([
                'subscribe' => __('messages.mercadopago_no_checkout_url'),
            ]);
        }

        return Inertia::location($result['checkout_url']);
    }

    public function return(
        Request $request,
        BarbershopPlatformSubscriptionSyncService $syncService,
        PaymentEmailService $paymentEmailService,
    ): Response|RedirectResponse {
        $user = $request->user();

        abort_unless($user->isBarbershopAccount(), 403);

        $preapprovalId = $request->query('preapproval_id')
            ?? $request->query('preapprovalId')
            ?? $request->query('id');

        try {
            $syncService->syncPendingForBarbershop(
                $user,
                is_string($preapprovalId) && $preapprovalId !== '' ? $preapprovalId : null,
            );
        } catch (\Throwable) {
            // Webhook will reconcile; show return page with current status.
        }

        $subscription = BarbershopPlatformSubscription::query()
            ->where('barbershop_user_id', $user->id)
            ->latest()
            ->first();

        $paymentEmailMismatch = $paymentEmailService->mismatchForPayerEmail(
            $user,
            $subscription?->payer_email,
        );

        if ($subscription?->isActive()) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'platform-subscription-active')
                ->with('prompt_profile_photo', true)
                ->with('payment_email_mismatch', $paymentEmailMismatch);
        }

        return Inertia::render('Platform/SubscribeReturn', [
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'status_label' => $subscription->statusLabel(),
                'is_active' => $subscription->isActive(),
            ] : null,
            'paymentEmailMismatch' => $paymentEmailMismatch,
        ]);
    }
}
