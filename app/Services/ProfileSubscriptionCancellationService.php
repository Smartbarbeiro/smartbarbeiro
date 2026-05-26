<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscriptionCancellationService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private ProfileSubscriptionSyncService $syncService,
    ) {}

    public function cancel(ProfileSubscription $subscription, User $cancelledBy): ProfileSubscription
    {
        if (! $subscription->isCancellable()) {
            throw new \InvalidArgumentException('This subscription cannot be cancelled.');
        }

        if ($subscription->mercadopago_preapproval_id && $this->mercadoPago->isConfigured()) {
            try {
                $preapproval = $this->mercadoPago->cancelPreApproval(
                    $subscription->mercadopago_preapproval_id,
                );

                return $this->syncService->syncFromPreApproval($preapproval)
                    ?? $this->markCancelled($subscription);
            } catch (MPApiException $exception) {
                Log::warning('Mercado Pago cancellation failed', [
                    'subscription_id' => $subscription->id,
                    'message' => $this->mercadoPago->apiExceptionMessage($exception),
                ]);

                throw $exception;
            }
        }

        return $this->markCancelled($subscription);
    }

    private function markCancelled(ProfileSubscription $subscription): ProfileSubscription
    {
        $subscription->update([
            'status' => ProfileSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }
}
