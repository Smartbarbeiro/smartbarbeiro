<?php

namespace App\Http\Controllers;

use App\Models\BarbershopMembership;
use App\Models\User;
use App\Services\BarbershopClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarbershopPreferredHaircutDayController extends Controller
{
    public function update(
        string $username,
        Request $request,
        BarbershopClientAccessService $clientAccess,
    ): RedirectResponse {
        $barbershop = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        $member = $request->user();

        if ($member->id === $barbershop->id) {
            abort(403);
        }

        if (! $clientAccess->hasSignedUp($barbershop, $member)) {
            abort(403);
        }

        $validated = $request->validate([
            'preferred_haircut_day' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $membership = BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $member->id,
        ]);

        $membership->update([
            'preferred_haircut_day' => $validated['preferred_haircut_day'],
        ]);

        return redirect()
            ->route('profile.public', $barbershop->username)
            ->with('status', 'preferred-haircut-day-saved');
    }
}
