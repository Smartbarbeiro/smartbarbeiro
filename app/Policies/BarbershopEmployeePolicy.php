<?php

namespace App\Policies;

use App\Models\BarbershopEmployee;
use App\Models\User;

class BarbershopEmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBarbershop();
    }

    public function create(User $user): bool
    {
        return $user->isBarbershop();
    }

    public function update(User $user, BarbershopEmployee $employee): bool
    {
        return $user->isBarbershop() && $user->id === $employee->barbershop_user_id;
    }

    public function delete(User $user, BarbershopEmployee $employee): bool
    {
        return $user->isBarbershop() && $user->id === $employee->barbershop_user_id;
    }
}
