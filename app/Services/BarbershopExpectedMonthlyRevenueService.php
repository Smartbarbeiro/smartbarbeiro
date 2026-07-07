<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Models\User;

class BarbershopExpectedMonthlyRevenueService
{
    /**
     * @return array{
     *     amount: float,
     *     formatted_amount: string,
     *     active_plans_count: int,
     *     service_plan_amount: float,
     *     profile_subscription_amount: float
     * }
     */
    public function payloadFor(User $barbershop): array
    {
        abort_unless($barbershop->isBarbershop(), 403);

        $servicePlanTotal = (float) $barbershop->servicePlanSubscribers()
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->sum('monthly_total');

        $activeServicePlanCount = $barbershop->servicePlanSubscribers()
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->count();

        $profilePlan = $barbershop->subscriptionPlan;
        $profileMonthlyAmount = $profilePlan && $profilePlan->is_enabled
            ? (float) $profilePlan->monthly_amount
            : 0.0;

        $activeProfileCount = $barbershop->subscribers()
            ->whereIn('status', ProfileSubscription::activeStatuses())
            ->count();

        $profileTotal = $profileMonthlyAmount * $activeProfileCount;
        $total = $servicePlanTotal + $profileTotal;

        return [
            'amount' => $total,
            'formatted_amount' => $this->formatBrl($total),
            'active_plans_count' => $activeServicePlanCount + $activeProfileCount,
            'service_plan_amount' => $servicePlanTotal,
            'profile_subscription_amount' => $profileTotal,
        ];
    }

    private function formatBrl(float $amount): string
    {
        return 'R$ '.number_format($amount, 2, ',', '.');
    }
}
