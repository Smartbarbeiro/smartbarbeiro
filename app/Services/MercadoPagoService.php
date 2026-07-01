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

    public function createAuthorizedSubscription(
        string $reason,
        string $payerEmail,
        string $externalReference,
        string $backUrl,
        string $cardTokenId,
        float $amount,
        string $currencyId,
    ): PreApproval {
        $this->ensureConfigured();
        $this->assertSandboxCheckoutUsers($payerEmail);

        $client = new PreApprovalClient;

        $payload = [
            'reason' => mb_substr($reason, 0, 200),
            'payer_email' => $payerEmail,
            'external_reference' => $externalReference,
            'back_url' => $backUrl,
            'card_token_id' => $cardTokenId,
            'status' => 'authorized',
            'auto_recurring' => $this->buildAutoRecurringPayload($amount, $currencyId),
        ];

        try {
            return $client->create($payload);
        } catch (MPApiException $exception) {
            $this->logApiException($exception, [
                'operation' => 'create_authorized_preapproval',
                'external_reference' => $externalReference,
                'payload' => $this->redactPayloadForLog($payload),
            ]);

            throw $exception;
        }
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
        $this->assertSandboxCheckoutUsers($payerEmail);

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

    public function publicKey(): ?string
    {
        $key = config('mercadopago.public_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mobilePaymentConfig(): ?array
    {
        if (! $this->isConfigured() || ! $this->publicKey()) {
            return null;
        }

        return [
            'public_key' => $this->publicKey(),
            'currency_id' => config('mercadopago.currency_id', 'BRL'),
            'merchant_name' => (string) config('mercadopago.merchant_name'),
            'apple_pay_merchant_id' => config('mercadopago.apple_pay_merchant_id'),
            'google_pay_merchant_id' => config('mercadopago.google_pay_merchant_id'),
            'google_pay_gateway' => config('mercadopago.google_pay_gateway', 'example'),
            'google_pay_gateway_merchant_id' => config('mercadopago.google_pay_gateway_merchant_id'),
            'google_pay_environment' => config('mercadopago.google_pay_environment', 'test'),
        ];
    }

    public function usesTestCredentials(): bool
    {
        return str_starts_with((string) config('mercadopago.access_token'), 'TEST-');
    }

    public function assertSandboxTestBuyer(string $payerEmail): void
    {
        $this->assertSandboxCheckoutUsers($payerEmail);
    }

    public function assertSandboxTestCollector(): void
    {
        // Pairing is validated in assertSandboxCheckoutUsers().
    }

    public function assertSandboxCheckoutUsers(string $payerEmail): void
    {
        if (! $this->usesTestCredentials()) {
            return;
        }

        $collectorIsTest = $this->collectorIsTestUser();
        $buyerIsTest = str_ends_with(strtolower($payerEmail), '@testuser.com');

        if ($collectorIsTest && ! $buyerIsTest) {
            throw new \InvalidArgumentException(__('messages.mercadopago_test_buyer_required'));
        }

        if (! $collectorIsTest && $buyerIsTest) {
            throw new \InvalidArgumentException(__('messages.mercadopago_real_buyer_required'));
        }
    }

    public function collectorIsTestUser(): bool
    {
        if ($this->collectorIsTestUser !== null) {
            return $this->collectorIsTestUser;
        }

        if (! $this->usesTestCredentials()) {
            return $this->collectorIsTestUser = false;
        }

        $response = \Illuminate\Support\Facades\Http::withOptions([
            'verify' => $this->sslCertificatePath(),
        ])
            ->withToken((string) config('mercadopago.access_token'))
            ->acceptJson()
            ->get('https://api.mercadopago.com/users/me');

        if (! $response->successful()) {
            return $this->collectorIsTestUser = false;
        }

        $tags = $response->json('tags') ?? [];

        return $this->collectorIsTestUser = in_array('test_user', $tags, true);
    }

    private ?bool $collectorIsTestUser = null;

    private function sslCertificatePath(): ?string
    {
        $bundle = storage_path('certs/cacert.pem');

        return is_file($bundle) ? $bundle : null;
    }

    public function webhookSignatureConfigured(): bool
    {
        return filled(config('mercadopago.webhook_secret'));
    }

    public function verifyWebhookSignature(?string $xSignature, ?string $xRequestId, ?string $dataId): bool
    {
        $secret = config('mercadopago.webhook_secret');

        if (! filled($secret) || ! filled($xSignature)) {
            return false;
        }

        $timestamp = null;
        $signature = null;

        foreach (explode(',', $xSignature) as $part) {
            $part = trim($part);

            if (str_starts_with($part, 'ts=')) {
                $timestamp = substr($part, 3);
            } elseif (str_starts_with($part, 'v1=')) {
                $signature = substr($part, 3);
            }
        }

        if (! filled($timestamp) || ! filled($signature)) {
            return false;
        }

        $manifestParts = [];

        if (filled($dataId)) {
            $manifestParts[] = 'id:'.strtolower($dataId);
        }

        if (filled($xRequestId)) {
            $manifestParts[] = 'request-id:'.$xRequestId;
        }

        $manifestParts[] = 'ts:'.$timestamp;
        $manifest = implode(';', $manifestParts).';';
        $expected = hash_hmac('sha256', $manifest, (string) $secret);

        return hash_equals($expected, $signature);
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
