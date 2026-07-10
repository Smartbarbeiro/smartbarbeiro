<?php

namespace App\Services;

use App\Models\BarbershopPlatformSubscription;
use App\Models\ProfileSubscription;
use App\Models\User;

class PaymentEmailService
{
    public function normalize(?string $email): ?string
    {
        if (! filled($email)) {
            return null;
        }

        return strtolower(trim($email));
    }

    public function matchesAccount(User $user, ?string $payerEmail): bool
    {
        $normalized = $this->normalize($payerEmail);

        if ($normalized === null) {
            return true;
        }

        if ($this->normalize($user->email) === $normalized) {
            return true;
        }

        return $this->normalize($user->billing_email) === $normalized;
    }

    public function preferredPayerEmail(User $user): string
    {
        return $user->billing_email ?: $user->email;
    }

    /**
     * @return array{account_email: string, payer_email: string, billing_email: ?string}|null
     */
    public function mismatchForUser(User $user): ?array
    {
        $payerEmail = $this->firstMismatchedPayerEmail($user);

        if ($payerEmail === null) {
            return null;
        }

        return [
            'account_email' => $user->email,
            'payer_email' => $payerEmail,
            'billing_email' => $user->billing_email,
        ];
    }

    /**
     * @return array{account_email: string, payer_email: string, billing_email: ?string}|null
     */
    public function mismatchForPayerEmail(User $user, ?string $payerEmail): ?array
    {
        if ($this->matchesAccount($user, $payerEmail)) {
            return null;
        }

        $normalized = $this->normalize($payerEmail);

        if ($normalized === null) {
            return null;
        }

        return [
            'account_email' => $user->email,
            'payer_email' => $normalized,
            'billing_email' => $user->billing_email,
        ];
    }

    public function userHasSubscriptionWithPayerEmail(User $user, string $payerEmail): bool
    {
        $normalized = $this->normalize($payerEmail);

        if ($normalized === null) {
            return false;
        }

        return $this->subscriptionPayerEmails($user)->contains($normalized);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function subscriptionPayerEmails(User $user): \Illuminate\Support\Collection
    {
        $profileEmails = ProfileSubscription::query()
            ->where('subscriber_user_id', $user->id)
            ->whereNotNull('payer_email')
            ->pluck('payer_email');

        $platformEmails = BarbershopPlatformSubscription::query()
            ->where('barbershop_user_id', $user->id)
            ->whereNotNull('payer_email')
            ->pluck('payer_email');

        return $profileEmails
            ->merge($platformEmails)
            ->map(fn (?string $email) => $this->normalize($email))
            ->filter()
            ->unique()
            ->values();
    }

    private function firstMismatchedPayerEmail(User $user): ?string
    {
        foreach ($this->subscriptionPayerEmails($user) as $email) {
            if (! $this->matchesAccount($user, $email)) {
                return $email;
            }
        }

        return null;
    }
}
