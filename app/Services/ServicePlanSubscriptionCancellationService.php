<?php

namespace App\Services;

use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;
use Stripe\Exception\ApiErrorException;

class ServicePlanSubscriptionCancellationService
{
    public function __construct(
        private StripeServicePlanService $stripe,
        private ServicePlanSubscriptionSyncService $syncService,
        private MercadoPagoService $mercadoPago,
    ) {}

    public function cancel(ServicePlanSubscription $subscription, User $cancelledBy): ServicePlanSubscription
    {
        if (! $subscription->isCancellable()) {
            throw new \InvalidArgumentException(__('messages.subscription_cannot_be_cancelled'));
        }

        if ($subscription->stripe_subscription_id && $this->stripe->isConfigured()) {
            try {
                $stripeSubscription = $this->stripe->cancelSubscription($subscription->stripe_subscription_id);

                return $this->syncService->syncFromStripeSubscription($stripeSubscription)
                    ?? $this->markCancelled($subscription);
            } catch (ApiErrorException $exception) {
                Log::warning('Stripe service plan cancellation failed', [
                    'subscription_id' => $subscription->id,
                    'message' => $this->stripe->apiExceptionMessage($exception),
                ]);

                throw $exception;
            }
        }

        if ($subscription->mercadopago_preapproval_id && $this->mercadoPago->isConfigured()) {
            try {
                $preapproval = $this->mercadoPago->cancelPreApproval($subscription->mercadopago_preapproval_id);

                return $this->markCancelled($subscription->fresh());
            } catch (MPApiException $exception) {
                Log::warning('Mercado Pago legacy service plan cancellation failed', [
                    'subscription_id' => $subscription->id,
                    'message' => $this->mercadoPago->apiExceptionMessage($exception),
                ]);

                throw $exception;
            }
        }

        return $this->markCancelled($subscription);
    }

    private function markCancelled(ServicePlanSubscription $subscription): ServicePlanSubscription
    {
        $subscription->update([
            'status' => ServicePlanSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }
}
