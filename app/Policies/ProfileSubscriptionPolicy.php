<?php

namespace App\Policies;

use App\Models\ProfileSubscription;
use App\Models\User;

class ProfileSubscriptionPolicy
{
    public function cancel(User $user, ProfileSubscription $subscription): bool
    {
        return $subscription->subscriber_user_id === $user->id
            && $subscription->isCancellable();
    }

    public function viewAsCreator(User $user, ProfileSubscription $subscription): bool
    {
        return $subscription->creator_user_id === $user->id;
    }
}
