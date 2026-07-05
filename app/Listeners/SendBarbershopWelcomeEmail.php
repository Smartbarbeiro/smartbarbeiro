<?php

namespace App\Listeners;

use App\Mail\BarbershopWelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendBarbershopWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! $user->isBarbershopAccount()) {
            return;
        }

        Mail::to($user->email)->send(new BarbershopWelcomeMail($user));
    }
}
