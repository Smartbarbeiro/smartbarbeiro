<?php

namespace App\Services;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Models\User;

class AdminPlatformSubscriptionOverviewService
{
    /**
     * @return array{
     *     summary: array{
     *         amount: float,
     *         formatted_amount: string,
     *         paying_barbershops_count: int,
     *         plan_formatted_price: string,
     *     },
     *     barbershops: list<array{
     *         id: int,
     *         name: string,
     *         username: string|null,
     *         email: string,
     *         profile_url: string|null,
     *         status: string,
     *         status_label: string,
     *         formatted_monthly_amount: string,
     *         next_payment_date: string|null,
     *         subscribed_since: string|null,
     *     }>
     * }
     */
    public function payload(): array
    {
        $plan = BarbershopPlatformPlan::current();
        $monthlyAmount = (float) $plan->monthly_amount;

        $subscriptions = BarbershopPlatformSubscription::query()
            ->where('status', BarbershopPlatformSubscription::STATUS_AUTHORIZED)
            ->with(['barbershop:id,name,username,email,is_frozen,platform_subscription_exempt'])
            ->whereHas('barbershop', function ($query) {
                $query->barbershopAccounts()
                    ->where('platform_subscription_exempt', false);
            })
            ->orderByDesc('updated_at')
            ->get();

        $barbershops = $subscriptions
            ->map(function (BarbershopPlatformSubscription $subscription) use ($monthlyAmount, $plan) {
                /** @var User $barbershop */
                $barbershop = $subscription->barbershop;

                return [
                    'id' => $barbershop->id,
                    'name' => $barbershop->name,
                    'username' => $barbershop->username,
                    'email' => $barbershop->email,
                    'profile_url' => $barbershop->profileUrl(),
                    'is_frozen' => $barbershop->isFrozen(),
                    'status' => $subscription->status,
                    'status_label' => $subscription->statusLabel(),
                    'formatted_monthly_amount' => $plan->formattedPrice(),
                    'monthly_amount' => $monthlyAmount,
                    'next_payment_date' => $subscription->next_payment_date?->toIso8601String(),
                    'subscribed_since' => $subscription->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $count = count($barbershops);
        $total = $monthlyAmount * $count;

        return [
            'summary' => [
                'amount' => $total,
                'formatted_amount' => $this->formatBrl($total),
                'paying_barbershops_count' => $count,
                'plan_formatted_price' => $plan->formattedPrice(),
            ],
            'barbershops' => $barbershops,
        ];
    }

    private function formatBrl(float $amount): string
    {
        return 'R$ '.number_format($amount, 2, ',', '.');
    }
}
