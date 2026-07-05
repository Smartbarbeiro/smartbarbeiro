<?php

namespace App\Mail;

use App\Models\ServicePlanSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientServicePlanConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServicePlanSubscription $subscription,
        public bool $isPlanUpdate = false,
    ) {}

    public function envelope(): Envelope
    {
        $barbershopName = $this->subscription->creator?->name ?? config('app.name');

        $subject = $this->isPlanUpdate
            ? 'Plano atualizado — '.$barbershopName
            : 'Plano confirmado — '.$barbershopName;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $barbershop = $this->subscription->creator;
        $subscriber = $this->subscription->subscriber;

        return new Content(
            markdown: 'mail.client-service-plan-confirmed',
            with: [
                'clientName' => $subscriber?->name,
                'barbershopName' => $barbershop?->name,
                'isPlanUpdate' => $this->isPlanUpdate,
                'packageLabel' => $this->subscription->packageLabel(),
                'addonLabels' => $this->subscription->selectedAddonLabels(),
                'formattedTotal' => $this->subscription->formattedTotal(),
                'nextPaymentDate' => $this->subscription->next_payment_date?->timezone(config('app.timezone'))->format('d/m/Y'),
                'profileUrl' => $barbershop?->username
                    ? route('profile.public', $barbershop->username)
                    : route('home'),
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
