<?php

namespace App\Policies;

use App\Models\AcrylicQrOrder;
use App\Models\User;

class AcrylicQrOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AcrylicQrOrder $order): bool
    {
        return $user->isAdmin();
    }
}
