<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use Stripe\Checkout\Session;
use Stripe\Subscription;

class ServicePlanSubscriptionSyncService
{
    public function __construct(
        private StripeServicePlanService $stripe,
        private ServicePlanSubscriptionNotificationService $notifications,
    ) {}

    public function syncFromStripeSubscription(Subscription $stripeSubscription): ?ServicePlanSubscription
    {
        $subscription = $this->findLocalSubscription($stripeSubscription);

        if (! $subscription) {
            return null;
        }

        $previousStatus = $subscription->status;

        $status = $this->stripe->mapSubscriptionStatus($stripeSubscription->status);

        $subscription->fill([
            'stripe_subscription_id' => $stripeSubscription->id ?? $subscription->stripe_subscription_id,
            'status' => $status,
            'payer_email' => $stripeSubscription->metadata['payer_email'] ?? $subscription->payer_email,
            'next_payment_date' => isset($stripeSubscription->current_period_end)
                ? now()->createFromTimestamp($stripeSubscription->current_period_end)
                : $subscription->next_payment_date,
        ]);

        if ($status === ServicePlanSubscription::STATUS_CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }

        $subscription->save();

        $this->ensureMembership($subscription);
        $this->notifications->notifyAfterSync($subscription, $previousStatus);

        return $subscription;
    }

    public function syncByStripeId(string $stripeSubscriptionId): ?ServicePlanSubscription
    {
        $stripeSubscription = $this->stripe->retrieveSubscription($stripeSubscriptionId);

        return $this->syncFromStripeSubscription($stripeSubscription);
    }

    public function syncByCheckoutSessionId(string $sessionId): ?ServicePlanSubscription
    {
        $session = $this->stripe->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        if (! $session->subscription) {
            return null;
        }

        $stripeSubscription = $session->subscription instanceof Subscription
            ? $session->subscription
            : $this->stripe->retrieveSubscription((string) $session->subscription);

        $subscription = $this->findLocalSubscription($stripeSubscription, $session);

        if ($subscription && ! $subscription->stripe_subscription_id) {
            $subscription->update(['stripe_subscription_id' => $stripeSubscription->id]);
        }

        return $this->syncFromStripeSubscription($stripeSubscription);
    }

    private function findLocalSubscription(Subscription $stripeSubscription, ?Session $session = null): ?ServicePlanSubscription
    {
        $metadata = $stripeSubscription->metadata?->toArray() ?? [];

        if (filled($metadata['service_plan_subscription_id'] ?? null)) {
            $subscription = ServicePlanSubscription::query()->find($metadata['service_plan_subscription_id']);

            if ($subscription) {
                return $subscription;
            }
        }

        if (filled($metadata['external_reference'] ?? null)) {
            $subscription = ServicePlanSubscription::query()
                ->where('external_reference', $metadata['external_reference'])
                ->first();

            if ($subscription) {
                return $subscription;
            }
        }

        if ($session && filled($session->metadata['service_plan_subscription_id'] ?? null)) {
            return ServicePlanSubscription::query()->find($session->metadata['service_plan_subscription_id']);
        }

        if ($session && filled($session->metadata['external_reference'] ?? null)) {
            return ServicePlanSubscription::query()
                ->where('external_reference', $session->metadata['external_reference'])
                ->first();
        }

        if (filled($stripeSubscription->id)) {
            return ServicePlanSubscription::query()
                ->where('stripe_subscription_id', $stripeSubscription->id)
                ->first();
        }

        return null;
    }

    private function ensureMembership(ServicePlanSubscription $subscription): void
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
