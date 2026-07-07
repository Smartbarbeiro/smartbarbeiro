<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBarbershopAppointmentRequest;
use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Services\BarbershopAppointmentService;
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

    public function update(
        UpdateBarbershopAppointmentRequest $request,
        BarbershopAppointment $appointment,
        BarbershopAppointmentService $appointmentService,
    ): RedirectResponse {
        $employee = null;

        if ($request->filled('barbershop_employee_id')) {
            $employee = BarbershopEmployee::query()->findOrFail($request->integer('barbershop_employee_id'));
        }

        match ($request->string('action')->toString()) {
            'confirm' => $appointmentService->confirm($appointment, $employee),
            'reject' => $appointmentService->reject($appointment),
            'cancel' => $appointmentService->cancel($appointment),
            'complete' => $appointmentService->complete($appointment),
            'assign' => $appointmentService->assignEmployee($appointment, $employee),
            default => abort(422),
        };

        return back()->with('status', 'appointment-updated');
    }
}
