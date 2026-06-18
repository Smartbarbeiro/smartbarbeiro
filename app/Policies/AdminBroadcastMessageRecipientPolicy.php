<?php

namespace App\Policies;

use App\Models\AdminBroadcastMessageRecipient;
use App\Models\User;

class AdminBroadcastMessageRecipientPolicy
{
    public function dismiss(User $user, AdminBroadcastMessageRecipient $recipient): bool
    {
        return $recipient->recipient_user_id === $user->id;
    }
}
