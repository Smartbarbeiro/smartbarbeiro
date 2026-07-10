<?php

namespace App\Mail;

use App\Models\BarbershopAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BarbershopAppointmentRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BarbershopAppointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->appointment->displayName();

        return new Envelope(
            subject: 'Nova solicitação de agendamento — '.$clientName,
        );
    }

    public function content(): Content
    {
        $scheduledAt = $this->appointment->scheduled_at->timezone(config('app.timezone'));

        return new Content(
            markdown: 'mail.barbershop-appointment-requested',
            with: [
                'barbershopName' => $this->appointment->barbershop?->name,
                'clientName' => $this->appointment->displayName(),
                'serviceLabel' => $this->appointment->service_label,
                'scheduledDate' => $scheduledAt->format('d/m/Y'),
                'scheduledTime' => $scheduledAt->format('H:i'),
                'clientNotes' => $this->appointment->client_notes,
                'agendaUrl' => route('agenda.index', [
                    'date' => $scheduledAt->toDateString(),
                ]),
            ],
        );
    }
}
