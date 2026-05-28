<?php

namespace App\Http\Controllers;

use App\Models\ProfileSubscription;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscribeController extends Controller
{
    public function store(
        string $username,
        Request $request,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
        $creator = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();
        $subscriber = $request->user();

        if ($subscriber->id === $creator->id) {
            return redirect()->route('profile.public', $creator->username);
        }

        $plan = $creator->subscriptionPlan;

        if (! $plan?->is_enabled) {
            return redirect()
                ->route('profile.public', $creator->username)
                ->withErrors(['subscribe' => __('messages.profile_no_subscription_required')]);
        }

        if (! $mercadoPago->isConfigured()) {
            return back()->withErrors([
                'subscribe' => __('messages.payments_not_configured'),
            ]);
        }

        $existing = ProfileSubscription::query()
            ->where('creator_user_id', $creator->id)
            ->where('subscriber_user_id', $subscriber->id)
            ->whereIn('status', ProfileSubscription::activeStatuses())
            ->first();

        if ($existing) {
            return redirect()
                ->route('profile.public', $creator->username)
                ->with('status', 'already-subscribed');
        }

        $externalReference = 'profile-'.$creator->id.'-'.$subscriber->id.'-'.Str::lower(Str::random(8));

        $subscription = ProfileSubscription::updateOrCreate(
            [
                'creator_user_id' => $creator->id,
                'subscriber_user_id' => $subscriber->id,
            ],
            [
                'payer_email' => $subscriber->email,
                'external_reference' => $externalReference,
                'status' => ProfileSubscription::STATUS_PENDING,
            ],
        );

        $backUrl = route('profile.subscribe.return', $creator->username);

        try {
            $preapproval = $mercadoPago->createSubscriptionCheckout(
                reason: $plan->title,
                payerEmail: $subscriber->email,
                externalReference: $subscription->external_reference,
                backUrl: $backUrl,
                preapprovalPlanId: $plan->mercadopago_preapproval_plan_id,
                amount: $plan->mercadopago_preapproval_plan_id ? null : (float) $plan->monthly_amount,
                currencyId: $plan->mercadopago_preapproval_plan_id ? null : $plan->currency_id,
            );
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'subscribe' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        }

        $subscription->update([
            'mercadopago_preapproval_id' => $preapproval->id,
            'status' => $mercadoPago->mapPreApprovalStatus($preapproval->status),
        ]);

        if (! $preapproval->init_point) {
            return back()->withErrors([
                'subscribe' => __('messages.mercadopago_no_checkout_url'),
            ]);
        }

        return redirect()->away($preapproval->init_point);
    }

    public function return(
        string $username,
        Request $request,
        ProfileSubscriptionSyncService $syncService,
    ): Response {
        $creator = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();
        $subscriber = $request->user();

        $subscription = ProfileSubscription::query()
            ->where('creator_user_id', $creator->id)
            ->where('subscriber_user_id', $subscriber->id)
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

        return Inertia::render('Profile/SubscribeReturn', [
            'creator' => [
                'name' => $creator->name,
                'username' => $creator->username,
            ],
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'is_active' => $subscription->isActive(),
            ] : null,
        ]);
    }
}
