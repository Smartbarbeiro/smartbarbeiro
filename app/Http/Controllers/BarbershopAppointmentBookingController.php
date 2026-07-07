<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarbershopAppointmentRequest;
use App\Models\User;
use App\Services\BarbershopAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $date = Carbon::parse($request->string('date')->toString() ?: now()->toDateString())->startOfDay();

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $appointmentService->availableSlotsForDate($barbershop, $date),
        ]);
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

        $appointmentService->createBooking(
            $barbershop,
            $request->user(),
            $request->validated(),
        );

        return back()->with('status', 'appointment-requested');
    }
}
