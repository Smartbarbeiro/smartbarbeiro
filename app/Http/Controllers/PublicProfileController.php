<?php

namespace App\Http\Controllers;

use App\Models\BarbershopMembership;
use App\Models\ProfileSubscription;
use App\Models\User;
use App\Services\ProfileAccessService;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(
        private ProfileAccessService $profileAccess,
    ) {}

    public function show(string $username): Response
    {
        $user = User::with('subscriptionPlan')
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->firstOrFail();

        $viewer = auth()->user();
        $canView = $this->profileAccess->canViewProfile($user, $viewer);
        $plan = $this->profileAccess->planPayload($user->subscriptionPlan);

        $activeSubscription = $viewer
            ? ProfileSubscription::query()
                ->where('creator_user_id', $user->id)
                ->where('subscriber_user_id', $viewer->id)
                ->latest()
                ->first()
            : null;

        $hasMembership = $viewer
            ? BarbershopMembership::query()
                ->where('barbershop_user_id', $user->id)
                ->where('member_user_id', $viewer->id)
                ->exists()
            : false;

        $requiresPayment = (bool) ($user->subscriptionPlan?->is_enabled);
        $hasSignedUp = $activeSubscription?->isActive() ?? false;

        if (! $requiresPayment && $hasMembership) {
            $hasSignedUp = true;
        }

        return Inertia::render('Profile/Public', [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'profile_url' => $user->profileUrl(),
                'profile_photo_url' => $user->profile_photo_url,
                'member_since' => $user->created_at->format('F Y'),
            ],
            'isOwner' => $viewer?->id === $user->id,
            'canView' => $canView,
            'subscriptionPlan' => $plan,
            'subscribeUrl' => route('profile.subscribe', $user->username),
            'hasActiveSubscription' => $activeSubscription?->isActive() ?? false,
            'activeSubscription' => $activeSubscription
                ? $activeSubscription->toSummaryArray()
                : null,
            'mercadopagoConfigured' => app(\App\Services\MercadoPagoService::class)->isConfigured(),
            'requiresPayment' => $requiresPayment,
            'hasSignedUp' => $hasSignedUp,
        ]);
    }
}
