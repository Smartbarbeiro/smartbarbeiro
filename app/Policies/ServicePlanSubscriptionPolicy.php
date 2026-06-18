<?php

namespace App\Policies;

use App\Models\ServicePlanSubscription;
use App\Models\User;

class ServicePlanSubscriptionPolicy
{
    public function cancel(User $user, ServicePlanSubscription $subscription): bool
    {
        return $subscription->subscriber_user_id === $user->id
            && $subscription->isCancellable();
    }
}
