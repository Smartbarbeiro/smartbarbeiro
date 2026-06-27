<?php

namespace App\Policies;

use App\Models\ServicePlanSubscriptionPayment;
use App\Models\User;

class ServicePlanSubscriptionPaymentPolicy
{
    public function downloadNotaFiscal(User $user, ServicePlanSubscriptionPayment $payment): bool
    {
        return $payment->subscription?->subscriber_user_id === $user->id
            && $payment->isPaid();
    }
}
