<?php

namespace App\Http\Controllers;

use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\AcrylicQrOrderService;
use App\Services\BarbershopClientAccessService;
use App\Services\BarbershopServicePlanService;
use App\Services\ProfileAccessService;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(
        private ProfileAccessService $profileAccess,
        private BarbershopClientAccessService $clientAccess,
    ) {}

    public function show(string $username, BarbershopServicePlanService $servicePlanService, AcrylicQrOrderService $acrylicQrOrderService): Response
    {
        $user = User::with('subscriptionPlan')
            ->where('username', $username)
            ->firstOrFail();

        if (! $user->hasPublicProfile()) {
            abort(404);
        }

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

        $activeServicePlanSubscription = $viewer
            ? ServicePlanSubscription::query()
                ->where('creator_user_id', $user->id)
                ->where('subscriber_user_id', $viewer->id)
                ->latest()
                ->first()
            : null;

        $membership = $viewer
            ? $this->clientAccess->membershipFor($user, $viewer)
            : null;

        $hasSignedUp = $viewer
            ? $this->clientAccess->hasSignedUp($user, $viewer)
            : false;

        $needsPreferredHaircutDay = $viewer
            ? $this->clientAccess->needsPreferredHaircutDay($user, $viewer)
            : false;

        return Inertia::render('Profile/Public', [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'profile_url' => $user->profileUrl(),
                'profile_photo_url' => $user->profile_photo_url,
                'member_since' => $user->created_at->translatedFormat('F Y'),
            ],
            'isOwner' => $viewer?->id === $user->id,
            'acrylicQrOrder' => $viewer?->id === $user->id
                ? $acrylicQrOrderService->activeOrderPayloadFor($user)
                : null,
            'canView' => $canView,
            'subscriptionPlan' => $plan,
            'subscribeUrl' => route('profile.subscribe', $user->username),
            'hasActiveSubscription' => $activeSubscription?->isActive() ?? false,
            'activeSubscription' => $activeSubscription
                ? $activeSubscription->toSummaryArray()
                : null,
            'hasActiveServicePlanSubscription' => $activeServicePlanSubscription?->isActive() ?? false,
            'activeServicePlanSubscription' => $activeServicePlanSubscription?->isActive()
                ? $activeServicePlanSubscription->toSummaryArray()
                : null,
            'pendingServicePlanSubscription' => ($activeServicePlanSubscription && ! $activeServicePlanSubscription->isActive())
                ? $activeServicePlanSubscription->toSummaryArray()
                : null,
            'mercadopagoConfigured' => app(\App\Services\MercadoPagoService::class)->isConfigured(),
            'requiresPayment' => (bool) ($user->subscriptionPlan?->is_enabled),
            'hasSignedUp' => $hasSignedUp,
            'needsPreferredHaircutDay' => $needsPreferredHaircutDay,
            'preferredHaircutDay' => $membership?->preferred_haircut_day,
            'servicePlans' => $servicePlanService->publicPlansPayload($user),
        ]);
    }
}
