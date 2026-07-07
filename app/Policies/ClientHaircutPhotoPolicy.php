<?php

namespace App\Policies;

use App\Models\ClientHaircutPhoto;
use App\Models\User;

class ClientHaircutPhotoPolicy
{
    public function delete(User $user, ClientHaircutPhoto $clientHaircutPhoto): bool
    {
        return $clientHaircutPhoto->user_id === $user->id;
    }
}
