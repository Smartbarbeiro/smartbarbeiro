<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Models\User;

class BarbershopClientAccessService
{
    public function membershipFor(User $barbershop, User $member): ?BarbershopMembership
    {
        return BarbershopMembership::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('member_user_id', $member->id)
            ->first();
    }

    public function hasSignedUp(User $barbershop, User $member): bool
    {
        $activeSubscription = ProfileSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $member->id)
            ->whereIn('status', ProfileSubscription::activeStatuses())
            ->exists();

        if ($activeSubscription) {
            return true;
        }

        $activeServicePlanSubscription = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $member->id)
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->exists();

        if ($activeServicePlanSubscription) {
            return true;
        }

        $requiresPayment = (bool) $barbershop->subscriptionPlan?->is_enabled;

        if (! $requiresPayment) {
            return BarbershopMembership::query()
                ->where('barbershop_user_id', $barbershop->id)
                ->where('member_user_id', $member->id)
                ->exists();
        }

        return false;
    }

    public function needsPreferredHaircutDay(User $barbershop, User $member): bool
    {
        if ($member->id === $barbershop->id) {
            return false;
        }

        if (! $this->hasSignedUp($barbershop, $member)) {
            return false;
        }

        $membership = $this->membershipFor($barbershop, $member);

        return $membership === null || $membership->preferred_haircut_day === null;
    }
}
