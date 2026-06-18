<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Collection;

class BarbershopClientAudienceService
{
    /**
     * @return Collection<int, User>
     */
    public function clientsFor(User $barbershop): Collection
    {
        abort_unless($barbershop->isBarbershop(), 403);

        $clientIds = collect()
            ->merge(
                BarbershopMembership::query()
                    ->where('barbershop_user_id', $barbershop->id)
                    ->pluck('member_user_id'),
            )
            ->merge(
                $barbershop->subscribers()
                    ->whereIn('status', ProfileSubscription::activeStatuses())
                    ->pluck('subscriber_user_id'),
            )
            ->merge(
                $barbershop->servicePlanSubscribers()
                    ->whereIn('status', ServicePlanSubscription::activeStatuses())
                    ->pluck('subscriber_user_id'),
            )
            ->unique()
            ->filter(fn ($id) => $id !== $barbershop->id)
            ->values();

        if ($clientIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $clientIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int>  $recipientIds
     * @return Collection<int, User>
     */
    public function resolveRecipients(User $barbershop, array $recipientIds, string $audience): Collection
    {
        $clients = $this->clientsFor($barbershop);

        if ($audience === 'all') {
            return $clients;
        }

        $allowedIds = $clients->pluck('id');

        return $clients->whereIn('id', collect($recipientIds)->intersect($allowedIds));
    }
}
