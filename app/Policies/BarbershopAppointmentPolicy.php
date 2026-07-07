<?php

namespace App\Policies;

use App\Models\BarbershopAppointment;
use App\Models\User;

class BarbershopAppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBarbershop();
    }

    public function update(User $user, BarbershopAppointment $appointment): bool
    {
        return $user->isBarbershop() && $user->id === $appointment->barbershop_user_id;
    }

    public function book(User $user, User $barbershop): bool
    {
        if ($user->isBarbershop() || $user->isAdmin()) {
            return false;
        }

        return app(\App\Services\BarbershopClientAccessService::class)
            ->hasSignedUp($barbershop, $user);
    }

    public function cancelAsClient(User $user, BarbershopAppointment $appointment): bool
    {
        return $user->id === $appointment->client_user_id
            && $appointment->isActive();
    }
}
