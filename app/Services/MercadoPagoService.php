<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Client\PreApprovalPlan\PreApprovalPlanClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\PreApproval;
use MercadoPago\Resources\PreApprovalPlan;
use RuntimeException;

class MercadoPagoService
{
    public function __construct()
    {
        //
    }

    private function ensureConfigured(): void
    {
        $token = config('mercadopago.access_token');

        if (! $token) {
            throw new RuntimeException('MERCADOPAGO_ACCESS_TOKEN is not configured.');
        }

        MercadoPagoConfig::setAccessToken($token);

        if (config('mercadopago.runtime_environment') === 'local') {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }
    }

    public function createPreApprovalPlan(
        string $reason,
        float $amount,
        string $currencyId,
        string $backUrl,
    ): PreApprovalPlan {
        $this->ensureConfigured();

        $client = new PreApprovalPlanClient;

        return $client->create([
            'reason' => $reason,
            'back_url' => $backUrl,
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $amount,
                'currency_id' => $currencyId,
            ],
        ]);
    }

    public function updatePreApprovalPlan(
        string $planId,
        string $reason,
        float $amount,
        string $currencyId,
        string $backUrl,
    ): PreApprovalPlan {
        $this->ensureConfigured();

        $client = new PreApprovalPlanClient;

        return $client->update($planId, [
            'reason' => $reason,
            'back_url' => $backUrl,
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $amount,
                'currency_id' => $currencyId,
            ],
        ]);
    }

    public function createSubscriptionCheckout(
        string $reason,
        string $payerEmail,
        string $externalReference,
        string $backUrl,
        ?string $preapprovalPlanId = null,
        ?float $amount = null,
        ?string $currencyId = null,
    ): PreApproval {
        $this->ensureConfigured();

        $client = new PreApprovalClient;

        $payload = [
            'reason' => $reason,
            'payer_email' => $payerEmail,
            'external_reference' => $externalReference,
            'back_url' => $backUrl,
            'status' => 'pending',
        ];

        if ($preapprovalPlanId) {
            $payload['preapproval_plan_id'] = $preapprovalPlanId;
        } elseif ($amount !== null && $currencyId !== null) {
            $payload['auto_recurring'] = [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $amount,
                'currency_id' => $currencyId,
            ];
        }

        return $client->create($payload);
    }

    public function getPreApproval(string $preapprovalId): PreApproval
    {
        $this->ensureConfigured();

        return (new PreApprovalClient)->get($preapprovalId);
    }

    public function cancelPreApproval(string $preapprovalId): PreApproval
    {
        $this->ensureConfigured();

        return (new PreApprovalClient)->update($preapprovalId, [
            'status' => 'cancelled',
        ]);
    }

    public function mapPreApprovalStatus(?string $status): string
    {
        return match ($status) {
            'authorized', 'active' => ProfileSubscription::STATUS_AUTHORIZED,
            'paused' => ProfileSubscription::STATUS_PAUSED,
            'cancelled', 'cancelled_by_payer', 'cancelled_by_collector' => ProfileSubscription::STATUS_CANCELLED,
            default => ProfileSubscription::STATUS_PENDING,
        };
    }

    public function isConfigured(): bool
    {
        return filled(config('mercadopago.access_token'));
    }

    /**
     * @throws MPApiException
     */
    public function apiExceptionMessage(MPApiException $exception): string
    {
        $content = $exception->getApiResponse()?->getContent();

        if (is_array($content) && isset($content['message'])) {
            return (string) $content['message'];
        }

        return $exception->getMessage();
    }
}
