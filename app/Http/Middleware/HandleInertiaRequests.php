<?php

namespace App\Http\Middleware;

use App\Services\AdminBroadcastMessageService;
use App\Services\BarbershopAppointmentService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge(
                    $user->append('is_administrator')->toArray(),
                    [
                        'is_barbershop' => $user->isBarbershop(),
                        'primary_barbershop_username' => $user->isBarbershop()
                            ? null
                            : $user->primaryBarbershop()?->username,
                    ],
                ) : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'statusMessage' => function () use ($request) {
                    $status = $request->session()->get('status');

                    if (! is_string($status) || $status === '') {
                        return null;
                    }

                    $translationKey = "messages.status.{$status}";

                    return __($translationKey) !== $translationKey
                        ? __($translationKey)
                        : null;
                },
            ],
            'locale' => app()->getLocale(),
            'platformMessages' => fn () => $user && ! $user->isAdmin()
                ? app(AdminBroadcastMessageService::class)->pendingPopupsFor($user)
                : [],
            'clientBooking' => fn () => $user
                ? app(BarbershopAppointmentService::class)->clientBookingPayload($user)
                : null,
            'pendingAgendaCount' => fn () => $user?->isBarbershop()
                ? app(BarbershopAppointmentService::class)->pendingAppointmentsCount($user)
                : 0,
        ];
    }
}
