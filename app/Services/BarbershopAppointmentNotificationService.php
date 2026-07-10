<?php

namespace App\Services;

use App\Mail\BarbershopAppointmentRequestedMail;
use App\Mail\ClientAppointmentConfirmedMail;
use App\Mail\ClientAppointmentRejectedMail;
use App\Models\BarbershopAppointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class BarbershopAppointmentNotificationService
{
    public function notifyRequested(BarbershopAppointment $appointment): void
    {
        if (! $this->claimNotification('requested', $appointment->id, $appointment->status)) {
            return;
        }

        $appointment->loadMissing(['barbershop', 'client']);

        $recipientEmail = $appointment->barbershop?->email;

        if (! filled($recipientEmail)) {
            return;
        }

        Mail::to($recipientEmail)->send(new BarbershopAppointmentRequestedMail($appointment));
    }

    public function notifyConfirmed(BarbershopAppointment $appointment): void
    {
        if (! $this->claimNotification('confirmed', $appointment->id, $appointment->status)) {
            return;
        }

        $appointment->loadMissing(['barbershop', 'client', 'employee']);

        $recipientEmail = $appointment->client?->email;

        if (! filled($recipientEmail)) {
            return;
        }

        Mail::to($recipientEmail)->send(new ClientAppointmentConfirmedMail($appointment));
    }

    public function notifyRejected(BarbershopAppointment $appointment): void
    {
        if (! $this->claimNotification('rejected', $appointment->id, $appointment->status)) {
            return;
        }

        $appointment->loadMissing(['barbershop', 'client']);

        $recipientEmail = $appointment->client?->email;

        if (! filled($recipientEmail)) {
            return;
        }

        Mail::to($recipientEmail)->send(new ClientAppointmentRejectedMail($appointment));
    }

    private function claimNotification(string $type, int $appointmentId, string $status): bool
    {
        return Cache::add(
            "appointment-mail:{$type}:{$appointmentId}:{$status}",
            true,
            now()->addHour(),
        );
    }
}
