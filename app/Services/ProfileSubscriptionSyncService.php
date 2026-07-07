<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use MercadoPago\Resources\PreApproval;

class ProfileSubscriptionSyncService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
    ) {}

    public function syncFromPreApproval(PreApproval $preapproval): ?ProfileSubscription
    {
        $subscription = ProfileSubscription::query()
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

        if ($status === ProfileSubscription::STATUS_CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }

        $subscription->save();

        return $subscription;
    }

    public function syncByMercadoPagoId(string $preapprovalId): ?ProfileSubscription
    {
        $preapproval = $this->mercadoPago->getPreApproval($preapprovalId);

        return $this->syncFromPreApproval($preapproval);
    }
}
