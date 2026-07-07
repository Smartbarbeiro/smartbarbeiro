<?php

namespace App\Services;

use App\Models\ProfileSubscription;
use App\Models\ProfileSubscriptionPlan;
use App\Models\User;

class ProfileAccessService
{
    public function canViewProfile(User $creator, ?User $viewer): bool
    {
        if ($viewer && $viewer->id === $creator->id) {
            return true;
        }

        $plan = $creator->subscriptionPlan;

        if (! $plan || ! $plan->is_enabled) {
            return true;
        }

        if (! $viewer) {
            return false;
        }

        return ProfileSubscription::query()
            ->where('creator_user_id', $creator->id)
            ->where('subscriber_user_id', $viewer->id)
            ->whereIn('status', ProfileSubscription::activeStatuses())
            ->exists();
    }

    public function planPayload(?ProfileSubscriptionPlan $plan): ?array
    {
        if (! $plan) {
            return null;
        }

        return [
            'is_enabled' => $plan->is_enabled,
            'title' => $plan->title,
            'description' => $plan->description,
            'monthly_amount' => (float) $plan->monthly_amount,
            'formatted_price' => $plan->formattedPrice(),
            'currency_id' => $plan->currency_id,
        ];
    }
}
