<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarbershopOwnerAppointmentRequest;
use App\Http\Requests\UpdateBarbershopAppointmentRequest;
use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Services\BarbershopAppointmentService;
use App\Services\BarbershopAppointmentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarbershopAppointmentController extends Controller
{
    public function index(Request $request, BarbershopAppointmentService $appointmentService): Response
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        return Inertia::render('Appointments/Index', [
            'agenda' => $appointmentService->agendaPayload($user, $request->string('date')->toString() ?: null),
        ]);
    }

    public function store(
        StoreBarbershopOwnerAppointmentRequest $request,
        BarbershopAppointmentService $appointmentService,
    ): RedirectResponse {
        $appointmentService->createOwnerBooking($request->user(), $request->validated());

        return back()->with('status', 'appointment-created');
    }

    public function update(
        UpdateBarbershopAppointmentRequest $request,
        BarbershopAppointment $appointment,
        BarbershopAppointmentService $appointmentService,
        BarbershopAppointmentNotificationService $notificationService,
    ): RedirectResponse {
        $employee = null;

        if ($request->filled('barbershop_employee_id')) {
            $employee = BarbershopEmployee::query()->findOrFail($request->integer('barbershop_employee_id'));
        }

        $action = $request->string('action')->toString();

        match ($action) {
            'confirm' => $appointment = $appointmentService->confirm($appointment, $employee),
            'reject' => $appointment = $appointmentService->reject($appointment),
            'cancel' => $appointment = $appointmentService->cancel($appointment),
            'complete' => $appointment = $appointmentService->complete($appointment),
            'assign' => $appointment = $appointmentService->assignEmployee($appointment, $employee),
            default => abort(422),
        };

        if ($action === 'confirm') {
            $notificationService->notifyConfirmed($appointment);
        }

        if ($action === 'reject') {
            $notificationService->notifyRejected($appointment);
        }

        return back()->with('status', 'appointment-updated');
    }
}
