<?php

namespace App\Http\Controllers;

use App\Models\BarbershopMembership;
use App\Models\ProfileSubscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarbershopMembershipController extends Controller
{
    public function store(string $username, Request $request): RedirectResponse
    {
        $barbershop = User::with('subscriptionPlan')
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $member = $request->user();

        if ($member->id === $barbershop->id) {
            return redirect()->route('profile.public', $barbershop->username);
        }

        if ($barbershop->subscriptionPlan?->is_enabled) {
            return redirect()
                ->route('profile.public', $barbershop->username)
                ->withErrors([
                    'signup' => __('messages.barbershop_paid_signup_required'),
                ]);
        }

        $existingSubscription = ProfileSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $member->id)
            ->whereIn('status', ProfileSubscription::activeStatuses())
            ->exists();

        if ($existingSubscription) {
            return redirect()
                ->route('profile.public', $barbershop->username)
                ->with('status', 'already-signed-up');
        }

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $member->id,
        ]);

        return redirect()
            ->route('profile.public', $barbershop->username)
            ->with('status', 'barbershop-signup-success');
    }
}
