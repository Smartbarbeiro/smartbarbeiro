<?php

namespace App\Mail;

use App\Models\BarbershopMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BarbershopMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BarbershopMessage $message,
        public User $barbershop,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.barbershop-message',
            with: [
                'messageSubject' => $this->message->subject,
                'messageBody' => $this->message->body,
                'barbershopName' => $this->barbershop->name,
                'recipientName' => $this->recipient->name,
                'inboxUrl' => route('messages.show', $this->message),
            ],
        );
    }
}
