<?php

namespace App\Mail;

use App\Models\BarbershopAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientAppointmentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BarbershopAppointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        $barbershopName = $this->appointment->barbershop?->name ?? config('app.name');

        return new Envelope(
            subject: 'Agendamento não confirmado — '.$barbershopName,
        );
    }

    public function content(): Content
    {
        $scheduledAt = $this->appointment->scheduled_at->timezone(config('app.timezone'));

        return new Content(
            markdown: 'mail.client-appointment-rejected',
            with: [
                'clientName' => $this->appointment->displayName(),
                'barbershopName' => $this->appointment->barbershop?->name,
                'serviceLabel' => $this->appointment->service_label,
                'scheduledDate' => $scheduledAt->format('d/m/Y'),
                'scheduledTime' => $scheduledAt->format('H:i'),
                'appointmentsUrl' => route('client.appointments.index'),
            ],
        );
    }
}
