<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;

class ServicePlanCheckoutService
{
    public function __construct(
        private BarbershopServicePlanService $servicePlanService,
        private StripeServicePlanService $stripe,
        private ServicePlanSubscriptionSyncService $syncService,
    ) {}

    /**
     * @return array{subscription: ServicePlanSubscription, checkout_url: string}
     */
    public function startCheckout(
        User $barbershop,
        User $subscriber,
        string $packageType,
        array $addonIds,
    ): array {
        if (! $this->stripe->isConfigured()) {
            throw new \InvalidArgumentException(__('messages.payments_not_configured'));
        }

        if (! $this->stripe->acceptsPaymentsFor($barbershop)) {
            throw new \InvalidArgumentException(__('messages.stripe_connect_not_ready'));
        }

        $selection = $this->servicePlanService->validateCheckoutSelection(
            $barbershop,
            $packageType,
            $addonIds,
        );

        $subscription = $this->savePendingSelection(
            $barbershop,
            $subscriber,
            $packageType,
            $addonIds,
        );

        $successUrl = route('service-plan.subscribe.return', $barbershop->username).'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('profile.public', $barbershop->username).'#pagamento';

        $checkoutUrl = $this->stripe->createWebCheckoutSession(
            $subscription,
            $subscriber,
            $selection,
            $successUrl,
            $cancelUrl,
            $barbershop,
        );

        return [
            'subscription' => $subscription,
            'checkout_url' => $checkoutUrl,
        ];
    }

    /**
     * @return array{
     *     customer_id: string,
     *     customer_ephemeral_key_secret: string,
     *     payment_intent_client_secret: string,
     *     subscription_id: string,
     *     status: string
     * }
     */
    public function prepareMobileCheckout(
        User $barbershop,
        User $subscriber,
        string $packageType,
        array $addonIds,
    ): array {
        if (! $this->stripe->isConfigured()) {
            throw new \InvalidArgumentException(__('messages.payments_not_configured'));
        }

        if (! $this->stripe->acceptsPaymentsFor($barbershop)) {
            throw new \InvalidArgumentException(__('messages.stripe_connect_not_ready'));
        }

        $selection = $this->servicePlanService->validateCheckoutSelection(
            $barbershop,
            $packageType,
            $addonIds,
        );

        $subscription = $this->savePendingSelection(
            $barbershop,
            $subscriber,
            $packageType,
            $addonIds,
        );

        $prepared = $this->stripe->prepareMobileSubscription(
            $subscription,
            $subscriber,
            $selection,
            $barbershop,
        );

        return [
            ...$prepared,
            'status' => 'requires_payment',
        ];
    }

    /**
     * @return array{subscription: ServicePlanSubscription, status: string}
     */
    public function confirmMobileCheckout(
        User $barbershop,
        User $subscriber,
        string $stripeSubscriptionId,
    ): array {
        if (! $this->stripe->isConfigured()) {
            throw new \InvalidArgumentException(__('messages.payments_not_configured'));
        }

        $subscription = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $subscriber->id)
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if (! $subscription) {
            throw new \InvalidArgumentException(__('messages.stripe_subscription_not_found'));
        }

        try {
            $stripeSubscription = $this->stripe->retrieveSubscription($stripeSubscriptionId);
        } catch (ApiErrorException $exception) {
            throw new \InvalidArgumentException($this->stripe->apiExceptionMessage($exception));
        }

        $subscription = $this->syncService->syncFromStripeSubscription($stripeSubscription)
            ?? $subscription;

        if (! $subscription->isActive()) {
            throw new \InvalidArgumentException(__('messages.stripe_subscription_not_active'));
        }

        $this->ensureMembership($subscription);

        return [
            'subscription' => $subscription->fresh(),
            'status' => $subscription->status,
        ];
    }

    public function savePendingSelection(
        User $barbershop,
        User $subscriber,
        string $packageType,
        array $addonIds,
    ): ServicePlanSubscription {
        if ($subscriber->id === $barbershop->id) {
            throw new \InvalidArgumentException(__('messages.service_plan_owner_cannot_subscribe'));
        }

        $selection = $this->servicePlanService->validateCheckoutSelection(
            $barbershop,
            $packageType,
            $addonIds,
        );

        $existing = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $subscriber->id)
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException(__('messages.service_plan_already_subscribed'));
        }

        return ServicePlanSubscription::updateOrCreate(
            [
                'creator_user_id' => $barbershop->id,
                'subscriber_user_id' => $subscriber->id,
            ],
            [
                'package_type' => $selection['package']->type,
                'selected_addon_ids' => $selection['addons']->pluck('id')->all(),
                'monthly_total' => $selection['monthly_total'],
                'currency_id' => strtoupper((string) config('stripe.currency', 'brl')),
                'payer_email' => $subscriber->email,
                'external_reference' => $this->externalReference($barbershop, $subscriber),
                'status' => ServicePlanSubscription::STATUS_PENDING,
            ],
        );
    }

    private function externalReference(User $barbershop, User $subscriber): string
    {
        return 'service-plan-'.$barbershop->id.'-'.$subscriber->id.'-'.Str::lower(Str::random(8));
    }

    public function ensureMembership(ServicePlanSubscription $subscription): void
    {
        if (! $subscription->isActive()) {
            return;
        }

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $subscription->creator_user_id,
            'member_user_id' => $subscription->subscriber_user_id,
        ]);
    }
}
