<?php

namespace App\Services;

use App\Models\ServicePlanSubscription;
use MercadoPago\Resources\PreApproval;

class ServicePlanSubscriptionSyncService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private ServicePlanCheckoutService $checkoutService,
    ) {}

    public function syncFromPreApproval(PreApproval $preapproval): ?ServicePlanSubscription
    {
        $subscription = ServicePlanSubscription::query()
            ->where('external_reference', $preapproval->external_reference)
            ->when($preapproval->id, fn ($query) => $query->orWhere('mercadopago_preapproval_id', $preapproval->id))
            ->first();

        if (! $subscription) {
            return null;
        }

        $status = $this->mercadoPago->mapPreApprovalStatus($preapproval->status);

        $subscription->fill([
            'mercadopago_preapproval_id' => $preapproval->id ?? $subscription->mercadopago_preapproval_id,
            'status' => $status,
            'payer_email' => $preapproval->payer_email ?? $subscription->payer_email,
            'next_payment_date' => $preapproval->next_payment_date,
        ]);

        if ($status === ServicePlanSubscription::STATUS_CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }

        $subscription->save();

        $this->checkoutService->ensureMembership($subscription);

        return $subscription;
    }

    public function syncByMercadoPagoId(string $preapprovalId): ?ServicePlanSubscription
    {
        $preapproval = $this->mercadoPago->getPreApproval($preapprovalId);

        return $this->syncFromPreApproval($preapproval);
    }
}
