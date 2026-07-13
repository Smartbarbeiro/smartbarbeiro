<?php

namespace App\Services;

use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use MercadoPago\Resources\PreApproval;

class BarbershopPlatformSubscriptionSyncService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
    ) {}

    public function syncFromPreApproval(PreApproval $preapproval): ?BarbershopPlatformSubscription
    {
        $subscription = $this->findLocalSubscription($preapproval);

        if (! $subscription) {
            return null;
        }

        $status = $this->mercadoPago->mapPreApprovalStatus($preapproval->status);

        $subscription->fill([
            'mercadopago_preapproval_id' => $preapproval->id ?? $subscription->mercadopago_preapproval_id,
            'status' => $status,
            'payer_email' => $preapproval->payer_email ?? $subscription->payer_email,
            'next_payment_date' => $this->optionalDate($preapproval, 'next_payment_date'),
        ]);

        if ($status === BarbershopPlatformSubscription::STATUS_CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }

        $subscription->save();

        return $subscription;
    }

    public function syncByMercadoPagoId(string $preapprovalId): ?BarbershopPlatformSubscription
    {
        $preapproval = $this->mercadoPago->getPreApproval($preapprovalId);

        return $this->syncFromPreApproval($preapproval);
    }

    /**
     * After plan-hosted checkout, MP creates a new preapproval without our
     * external_reference — match the logged-in barbershop by payer email.
     */
    public function syncPendingForBarbershop(User $barbershop, ?string $preapprovalId = null): ?BarbershopPlatformSubscription
    {
        if (filled($preapprovalId)) {
            return $this->syncByMercadoPagoId($preapprovalId);
        }

        $subscription = BarbershopPlatformSubscription::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->latest()
            ->first();

        if ($subscription?->mercadopago_preapproval_id) {
            return $this->syncByMercadoPagoId((string) $subscription->mercadopago_preapproval_id);
        }

        return $subscription;
    }

    private function findLocalSubscription(PreApproval $preapproval): ?BarbershopPlatformSubscription
    {
        if (filled($preapproval->external_reference)) {
            $byReference = BarbershopPlatformSubscription::query()
                ->where('external_reference', $preapproval->external_reference)
                ->first();

            if ($byReference) {
                return $byReference;
            }
        }

        if (filled($preapproval->id)) {
            $byId = BarbershopPlatformSubscription::query()
                ->where('mercadopago_preapproval_id', $preapproval->id)
                ->first();

            if ($byId) {
                return $byId;
            }
        }

        $payerEmail = filled($preapproval->payer_email)
            ? strtolower(trim((string) $preapproval->payer_email))
            : null;

        if (! $payerEmail) {
            return null;
        }

        return BarbershopPlatformSubscription::query()
            ->whereIn('status', [
                BarbershopPlatformSubscription::STATUS_PENDING,
                BarbershopPlatformSubscription::STATUS_PAUSED,
            ])
            ->where(function ($builder) use ($payerEmail) {
                $builder
                    ->whereRaw('LOWER(payer_email) = ?', [$payerEmail])
                    ->orWhereHas('barbershop', function ($barbershopQuery) use ($payerEmail) {
                        $barbershopQuery->whereRaw('LOWER(email) = ?', [$payerEmail]);
                    });
            })
            ->latest()
            ->first();
    }

    private function optionalDate(PreApproval $preapproval, string $property): mixed
    {
        try {
            return $preapproval->{$property};
        } catch (\Error) {
            return null;
        }
    }
}
