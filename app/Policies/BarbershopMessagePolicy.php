<?php

namespace App\Policies;

use App\Models\BarbershopMessage;
use App\Models\User;

class BarbershopMessagePolicy
{
    public function view(User $user, BarbershopMessage $message): bool
    {
        if ($message->barbershop_user_id === $user->id) {
            return true;
        }

        return $message->recipients()
            ->where('recipient_user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isBarbershop();
    }

    public function markAsRead(User $user, BarbershopMessage $message): bool
    {
        return $message->recipients()
            ->where('recipient_user_id', $user->id)
            ->exists();
    }
}
