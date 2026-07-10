<?php

namespace App\Services;

use App\Models\User;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;

class StripeConnectService
{
    public function __construct(
        private StripeServicePlanService $stripe,
    ) {}

    public function isEnabled(): bool
    {
        return $this->stripe->isConfigured() && (bool) config('stripe.connect_enabled', true);
    }

    public function createOnboardingUrl(User $barbershop): string
    {
        if (! $barbershop->isBarbershop()) {
            throw new \InvalidArgumentException(__('messages.stripe_connect_barbershop_only'));
        }

        $this->stripe->ensureConfigured();

        $accountId = $this->ensureExpressAccount($barbershop);

        $link = $this->stripe->client()->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => route('stripe-connect.refresh'),
            'return_url' => route('stripe-connect.return'),
            'type' => 'account_onboarding',
        ]);

        if (! is_string($link->url) || $link->url === '') {
            throw new \RuntimeException(__('messages.stripe_connect_onboarding_failed'));
        }

        return $link->url;
    }

    public function syncAccount(User $barbershop, ?Account $account = null): User
    {
        if (! filled($barbershop->stripe_connect_account_id) && $account === null) {
            return $barbershop;
        }

        $this->stripe->ensureConfigured();

        $account ??= $this->stripe->client()->accounts->retrieve(
            (string) $barbershop->stripe_connect_account_id,
        );

        $barbershop->forceFill([
            'stripe_connect_account_id' => $account->id,
            'stripe_connect_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_connect_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_connect_details_submitted' => (bool) ($account->details_submitted ?? false),
        ])->save();

        return $barbershop->fresh();
    }

    public function syncAccountById(string $accountId): ?User
    {
        $barbershop = User::query()
            ->where('stripe_connect_account_id', $accountId)
            ->first();

        if ($barbershop) {
            return $this->syncAccount($barbershop);
        }

        $account = $this->stripe->client()->accounts->retrieve($accountId);
        $userId = $account->metadata['user_id'] ?? null;

        if (! filled($userId)) {
            return null;
        }

        $barbershop = User::query()->find($userId);

        if (! $barbershop) {
            return null;
        }

        return $this->syncAccount($barbershop, $account);
    }

    public function clearAccount(User $barbershop): void
    {
        $barbershop->forceFill([
            'stripe_connect_account_id' => null,
            'stripe_connect_charges_enabled' => false,
            'stripe_connect_payouts_enabled' => false,
            'stripe_connect_details_submitted' => false,
        ])->save();
    }

    private function ensureExpressAccount(User $barbershop): string
    {
        if (filled($barbershop->stripe_connect_account_id)) {
            return (string) $barbershop->stripe_connect_account_id;
        }

        try {
            $account = $this->stripe->client()->accounts->create([
                'type' => 'express',
                'country' => strtoupper((string) config('stripe.connect_country', 'BR')),
                'email' => $barbershop->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'name' => $barbershop->name,
                    'url' => $barbershop->profileUrl(),
                ],
                'metadata' => [
                    'user_id' => (string) $barbershop->id,
                ],
            ]);
        } catch (ApiErrorException $exception) {
            throw new \RuntimeException($this->stripe->apiExceptionMessage($exception), 0, $exception);
        }

        $barbershop->forceFill([
            'stripe_connect_account_id' => $account->id,
            'stripe_connect_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_connect_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_connect_details_submitted' => (bool) ($account->details_submitted ?? false),
        ])->save();

        return $account->id;
    }
}
