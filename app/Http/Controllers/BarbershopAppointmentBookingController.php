<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarbershopAppointmentRequest;
use App\Models\User;
use App\Services\BarbershopAppointmentService;
use App\Services\BarbershopAppointmentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarbershopAppointmentBookingController extends Controller
{
    public function availability(
        string $username,
        Request $request,
        BarbershopAppointmentService $appointmentService,
    ): JsonResponse {
        $barbershop = User::query()
            ->where('username', $username)
            ->firstOrFail();

        abort_unless($barbershop->hasPublicProfile(), 404);

        $requestedDate = $request->string('date')->toString() ?: null;

        return response()->json(
            $appointmentService->bookingAvailabilityPayload($barbershop, $requestedDate),
        );
    }

    public function store(
        string $username,
        StoreBarbershopAppointmentRequest $request,
        BarbershopAppointmentService $appointmentService,
    ): RedirectResponse {
        $barbershop = User::query()
            ->where('username', $username)
            ->firstOrFail();

        abort_unless($barbershop->hasPublicProfile(), 404);

        $appointment = $appointmentService->createBooking(
            $barbershop,
            $request->user(),
            $request->validated(),
        );

        app(BarbershopAppointmentNotificationService::class)->notifyRequested($appointment);

        return redirect()
            ->route('client.appointments.index')
            ->with('status', 'appointment-requested');
    }
}
