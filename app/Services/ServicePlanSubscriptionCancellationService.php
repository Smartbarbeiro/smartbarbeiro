<?php

namespace App\Services;

use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class ServicePlanSubscriptionCancellationService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private ServicePlanSubscriptionSyncService $syncService,
    ) {}

    public function cancel(ServicePlanSubscription $subscription, User $cancelledBy): ServicePlanSubscription
    {
        if (! $subscription->isCancellable()) {
            throw new \InvalidArgumentException(__('messages.subscription_cannot_be_cancelled'));
        }

        if ($subscription->mercadopago_preapproval_id && $this->mercadoPago->isConfigured()) {
            try {
                $preapproval = $this->mercadoPago->cancelPreApproval(
                    $subscription->mercadopago_preapproval_id,
                );

                return $this->syncService->syncFromPreApproval($preapproval)
                    ?? $this->markCancelled($subscription);
            } catch (MPApiException $exception) {
                Log::warning('Mercado Pago service plan cancellation failed', [
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
