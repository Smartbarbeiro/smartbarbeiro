<?php

namespace App\Services;

use InvalidArgumentException;

class MercadoPagoWalletTokenService
{
    /**
     * Resolves a Mercado Pago card_token_id from a native wallet payload or direct token.
     */
    public function resolveCardTokenId(string $walletType, string $paymentToken): string
    {
        $trimmed = trim($paymentToken);

        if ($this->looksLikeMercadoPagoCardToken($trimmed)) {
            return $trimmed;
        }

        $decoded = json_decode($trimmed, true);

        if (! is_array($decoded)) {
            $decoded = $this->decodeApplePayPaymentData($trimmed);
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException(__('messages.wallet_token_invalid'));
        }

        $candidate = $this->findCardTokenInPayload($decoded);

        if ($candidate !== null && $this->looksLikeMercadoPagoCardToken($candidate)) {
            return $candidate;
        }

        if ($walletType === 'google_pay') {
            $candidate = $this->extractGooglePayToken($decoded);

            if ($candidate !== null && $this->looksLikeMercadoPagoCardToken($candidate)) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException(__('messages.wallet_token_invalid'));
    }

    public function looksLikeMercadoPagoCardToken(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/i', $value);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeApplePayPaymentData(string $paymentData): ?array
    {
        $decoded = base64_decode($paymentData, true);

        if ($decoded === false) {
            return null;
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findCardTokenInPayload(array $payload): ?string
    {
        foreach (['card_token_id', 'card_token', 'token', 'id'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $this->looksLikeMercadoPagoCardToken($payload[$key])) {
                return $payload[$key];
            }
        }

        foreach ($payload as $value) {
            if (! is_array($value)) {
                continue;
            }

            $nested = $this->findCardTokenInPayload($value);

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $googlePaymentData
     */
    private function extractGooglePayToken(array $googlePaymentData): ?string
    {
        $tokenizationData = data_get($googlePaymentData, 'paymentMethodData.tokenizationData')
            ?? data_get($googlePaymentData, 'tokenizationData');

        if (! is_array($tokenizationData) || ! isset($tokenizationData['token']) || ! is_string($tokenizationData['token'])) {
            return null;
        }

        $inner = json_decode($tokenizationData['token'], true);

        if (! is_array($inner)) {
            return null;
        }

        return $this->findCardTokenInPayload($inner);
    }
}
