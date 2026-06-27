<?php

namespace App\Services;

use App\Models\ServicePlanSubscriptionPayment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ServicePlanNotaFiscalPdfService
{
    public function download(
        ServicePlanSubscriptionPayment $payment,
        User $subscriber,
    ): Response {
        $subscription = $payment->subscription()
            ->with(['creator', 'subscriber'])
            ->firstOrFail();

        abort_unless($subscription->subscriber_user_id === $subscriber->id, 403);
        abort_unless($payment->isPaid(), 404);

        $barbershop = $subscription->creator;
        $issuedAt = $payment->paid_at ?? now();
        $filename = sprintf(
            'nota-fiscal-%s-%s-%02d.pdf',
            $barbershop->username ?? $barbershop->id,
            $payment->billing_year,
            $payment->billing_month,
        );

        return Pdf::loadView('pdf.service-plan-nota-fiscal', [
            'barbershopName' => $barbershop->name,
            'barbershopUsername' => $barbershop->username,
            'subscriberName' => $subscriber->name,
            'subscriberEmail' => $subscriber->email,
            'subscriberDocument' => $subscriber->tax_document,
            'packageLabel' => $subscription->packageLabel(),
            'periodLabel' => $payment->periodLabel(),
            'formattedAmount' => $payment->formattedAmount(),
            'paidAt' => $issuedAt->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'invoiceReference' => $payment->stripe_invoice_id ?? ('NF-'.$payment->id),
        ])
            ->setPaper('a4')
            ->download($filename);
    }
}
