<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use Illuminate\Support\Facades\Log;
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

        $this->configureRuntimeEnvironment();
    }

    private function configureRuntimeEnvironment(): void
    {
        if (config('mercadopago.runtime_environment') === 'local') {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

            return;
        }

        if (app()->environment('local') && ! $this->systemHasSslCertificates()) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }
    }

    private function systemHasSslCertificates(): bool
    {
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $path) {
            if (filled($path) && is_file($path)) {
                return true;
            }
        }

        return false;
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
            'reason' => mb_substr($reason, 0, 200),
            'payer_email' => $payerEmail,
            'external_reference' => $externalReference,
            'back_url' => $backUrl,
            'status' => 'pending',
        ];

        if ($preapprovalPlanId) {
            $payload['preapproval_plan_id'] = $preapprovalPlanId;
        } elseif ($amount !== null && $currencyId !== null) {
            $payload['auto_recurring'] = $this->buildAutoRecurringPayload($amount, $currencyId);
        }

        try {
            return $client->create($payload);
        } catch (MPApiException $exception) {
            $this->logApiException($exception, [
                'operation' => 'create_preapproval',
                'external_reference' => $externalReference,
                'back_url' => $backUrl,
                'uses_plan_id' => filled($preapprovalPlanId),
                'payload' => $this->redactPayloadForLog($payload),
            ]);

            throw $exception;
        }
    }

    public function checkoutUrl(PreApproval $preapproval): ?string
    {
        $url = $preapproval->sandbox_init_point ?? $preapproval->init_point;

        return filled($url) ? $url : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAutoRecurringPayload(float $amount, string $currencyId): array
    {
        return [
            'frequency' => 1,
            'frequency_type' => 'months',
            'transaction_amount' => round($amount, 2),
            'currency_id' => $currencyId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayloadForLog(array $payload): array
    {
        if (isset($payload['payer_email']) && is_string($payload['payer_email'])) {
            $email = $payload['payer_email'];
            $at = strrpos($email, '@');

            if ($at !== false) {
                $payload['payer_email'] = substr($email, 0, min(3, $at)).'***'.substr($email, $at);
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logApiException(MPApiException $exception, array $context = []): void
    {
        Log::warning('Mercado Pago API error', array_merge($context, [
            'status_code' => $exception->getApiResponse()?->getStatusCode(),
            'response' => $exception->getApiResponse()?->getContent(),
        ]));
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

    public function usesTestCredentials(): bool
    {
        return str_starts_with((string) config('mercadopago.access_token'), 'TEST-');
    }

    public function assertSandboxTestBuyer(string $payerEmail): void
    {
        if (! $this->usesTestCredentials()) {
            return;
        }

        if (str_ends_with(strtolower($payerEmail), '@testuser.com')) {
            return;
        }

        throw new \InvalidArgumentException(__('messages.mercadopago_test_buyer_required'));
    }

    /**
     * @throws MPApiException
     */
    public function apiExceptionMessage(MPApiException $exception): string
    {
        $content = $exception->getApiResponse()?->getContent();

        if (! is_array($content)) {
            return $exception->getMessage();
        }

        $parts = [];

        if (isset($content['message'])) {
            $parts[] = (string) $content['message'];
        }

        if (isset($content['error']) && is_string($content['error'])) {
            $parts[] = $content['error'];
        }

        if (isset($content['cause']) && is_array($content['cause'])) {
            foreach ($content['cause'] as $cause) {
                if (! is_array($cause)) {
                    continue;
                }

                $description = $cause['description'] ?? $cause['message'] ?? null;

                if (filled($description)) {
                    $parts[] = (string) $description;
                }
            }
        }

        $parts = array_values(array_unique(array_filter($parts)));
        $parts = array_map(fn (string $part) => $this->translateApiMessage($part), $parts);

        if ($parts !== []) {
            return implode(' — ', $parts);
        }

        return $exception->getMessage();
    }

    private function translateApiMessage(string $message): string
    {
        if (str_contains($message, 'Both payer and collector must be real or test users')) {
            return __('messages.mercadopago_sandbox_users_required');
        }

        return $message;
    }
}
