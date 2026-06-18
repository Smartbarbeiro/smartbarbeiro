<?php

namespace App\Mail;

use App\Models\AdminBroadcastMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminBroadcastMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdminBroadcastMessage $message,
        public User $admin,
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
            markdown: 'mail.admin-broadcast-message',
            with: [
                'messageSubject' => $this->message->subject,
                'messageBody' => $this->message->body,
                'adminName' => $this->admin->name,
                'recipientName' => $this->recipient->name,
                'appUrl' => route('dashboard'),
            ],
        );
    }
}
