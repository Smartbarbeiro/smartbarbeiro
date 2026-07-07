<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BarbershopWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $barbershop,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao '.config('app.name').' — próximos passos',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.barbershop-welcome',
            with: [
                'barbershopName' => $this->barbershop->name,
                'platformSubscribeUrl' => route('platform.subscribe'),
                'servicePlansUrl' => route('services.index'),
                'profileUrl' => route('profile.edit'),
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
