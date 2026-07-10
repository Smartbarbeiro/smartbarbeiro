<?php

namespace App\Http\Controllers;

use App\Models\BarbershopAppointment;
use App\Services\BarbershopAppointmentService;
use App\Services\BarbershopClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientAppointmentController extends Controller
{
    public function index(
        Request $request,
        BarbershopAppointmentService $appointmentService,
        BarbershopClientAccessService $clientAccess,
    ): Response {
        $client = $request->user();
        $barbershop = $client->primaryBarbershop();

        abort_unless($barbershop !== null, 404);
        abort_unless($clientAccess->hasSignedUp($barbershop, $client), 403);

        $booking = $appointmentService->clientBookingPayload($client);

        abort_unless($booking !== null, 403);

        return Inertia::render('Appointments/ClientIndex', [
            'barbershop' => [
                'name' => $barbershop->name,
                'username' => $barbershop->username,
            ],
            'appointments' => $appointmentService->clientAppointmentsPayload($client, $barbershop),
            'booking' => [
                'username' => $booking['username'],
                'defaultServiceLabel' => $booking['defaultServiceLabel'],
                'defaultPackageType' => $booking['defaultPackageType'],
            ],
        ]);
    }

    public function cancel(
        BarbershopAppointment $appointment,
        Request $request,
        BarbershopAppointmentService $appointmentService,
    ): RedirectResponse {
        $this->authorize('cancelAsClient', $appointment);

        $appointmentService->cancel($appointment);

        return redirect()
            ->route('client.appointments.index')
            ->with('status', 'appointment-cancelled');
    }
}
