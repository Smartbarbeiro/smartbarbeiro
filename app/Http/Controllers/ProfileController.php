<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\DeleteUserAccountService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private DeleteUserAccountService $deleteUserAccount,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('subscriptionPlan');

        $plan = $user->subscriptionPlan;

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'profileUrl' => $user->profileUrl(),
            'subscribeUrl' => $user->subscribeUrl(),
            'mercadopagoConfigured' => app(\App\Services\MercadoPagoService::class)->isConfigured(),
            'subscriptionPlan' => $plan ? [
                'is_enabled' => $plan->is_enabled,
                'title' => $plan->title,
                'description' => $plan->description,
                'monthly_amount' => (float) $plan->monthly_amount,
                'formatted_price' => $plan->formattedPrice(),
            ] : null,
            'activeSubscribersCount' => $user->subscribers()
                ->whereIn('status', \App\Models\ProfileSubscription::activeStatuses())
                ->count(),
            'subscribers' => $user->subscribers()
                ->with('subscriber:id,name,email,username')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn ($subscription) => [
                    ...$subscription->toSummaryArray(),
                    'subscriber' => [
                        'name' => $subscription->subscriber->name,
                        'email' => $subscription->subscriber->email,
                        'username' => $subscription->subscriber->username,
                    ],
                ]),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'username', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->boolean('remove_profile_photo')) {
            $user->deleteProfilePhoto();
        } elseif ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')->store(
                'profile-photos/'.$user->id,
                'public',
            );
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $this->deleteUserAccount->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
