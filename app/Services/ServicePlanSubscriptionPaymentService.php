<?php

namespace App\Services;

use App\Models\ServicePlanSubscription;
use App\Models\ServicePlanSubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Stripe\Invoice;

class ServicePlanSubscriptionPaymentService
{
    public function __construct(
        private StripeServicePlanService $stripe,
    ) {}

    public function syncSchedule(ServicePlanSubscription $subscription): void
    {
        $start = $subscription->created_at->copy()->startOfMonth();
        $end = now()->copy()->endOfYear();

        if ($subscription->cancelled_at) {
            $end = $subscription->cancelled_at->copy()->startOfMonth();
        }

        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $this->ensurePaymentSlot($subscription, $cursor->year, $cursor->month);
            $cursor->addMonth();
        }

        $this->markOverduePayments($subscription);
    }

    public function syncInvoicesFromStripe(ServicePlanSubscription $subscription): void
    {
        if (! filled($subscription->stripe_subscription_id) || ! $this->stripe->isConfigured()) {
            return;
        }

        $invoices = $this->stripe->client()->invoices->all([
            'subscription' => $subscription->stripe_subscription_id,
            'limit' => 100,
        ]);

        foreach ($invoices->data as $invoice) {
            $this->syncFromStripeInvoice($subscription, $invoice);
        }
    }

    public function syncFromStripeInvoice(
        ServicePlanSubscription $subscription,
        Invoice $invoice,
    ): ?ServicePlanSubscriptionPayment {
        if (! filled($invoice->period_start)) {
            return null;
        }

        $period = Carbon::createFromTimestamp($invoice->period_start)->startOfMonth();

        $payment = $this->ensurePaymentSlot(
            $subscription,
            $period->year,
            $period->month,
        );

        $amount = $this->resolveInvoiceAmount($invoice, $subscription);

        $payment->fill([
            'amount' => $amount,
            'currency_id' => strtoupper((string) ($invoice->currency ?? $subscription->currency_id ?? 'BRL')),
            'stripe_invoice_id' => $invoice->id,
        ]);

        if ($invoice->status === 'paid') {
            $payment->fill([
                'status' => ServicePlanSubscriptionPayment::STATUS_PAID,
                'paid_at' => filled($invoice->status_transitions->paid_at ?? null)
                    ? Carbon::createFromTimestamp($invoice->status_transitions->paid_at)
                    : now(),
                'failed_at' => null,
            ]);
        } elseif (in_array($invoice->status, ['open', 'uncollectible'], true)) {
            $payment->fill([
                'status' => ServicePlanSubscriptionPayment::STATUS_FAILED,
                'failed_at' => now(),
            ]);
        }

        $payment->save();

        return $payment;
    }

    /**
     * @return array<int, array{year: int, months: array<int, array<string, mixed>>}>
     */
    public function paymentHistoryPayload(ServicePlanSubscription $subscription): array
    {
        $this->syncSchedule($subscription);
        $this->syncInvoicesFromStripe($subscription);
        $this->markOverduePayments($subscription);

        $payments = $subscription->payments()
            ->orderBy('billing_year')
            ->orderBy('billing_month')
            ->get();

        return $this->groupPaymentsByYear($payments);
    }

    /**
     * @return Collection<int, ServicePlanSubscriptionPayment>
     */
    public function paymentsForYear(ServicePlanSubscription $subscription, int $year): Collection
    {
        return $subscription->payments()
            ->where('billing_year', $year)
            ->orderBy('billing_month')
            ->get();
    }

    private function ensurePaymentSlot(
        ServicePlanSubscription $subscription,
        int $year,
        int $month,
    ): ServicePlanSubscriptionPayment {
        return ServicePlanSubscriptionPayment::firstOrCreate(
            [
                'service_plan_subscription_id' => $subscription->id,
                'billing_year' => $year,
                'billing_month' => $month,
            ],
            [
                'status' => ServicePlanSubscriptionPayment::STATUS_PENDING,
                'amount' => $subscription->monthly_total,
                'currency_id' => strtoupper((string) ($subscription->currency_id ?? 'BRL')),
            ],
        );
    }

    private function markOverduePayments(ServicePlanSubscription $subscription): void
    {
        $now = now()->startOfMonth();

        $subscription->payments()
            ->where('status', ServicePlanSubscriptionPayment::STATUS_PENDING)
            ->get()
            ->each(function (ServicePlanSubscriptionPayment $payment) use ($now) {
                $due = now()
                    ->setDate($payment->billing_year, $payment->billing_month, 1)
                    ->startOfMonth();

                if ($due->lt($now)) {
                    $payment->update([
                        'status' => ServicePlanSubscriptionPayment::STATUS_OVERDUE,
                    ]);
                }
            });
    }

    private function resolveInvoiceAmount(Invoice $invoice, ServicePlanSubscription $subscription): float
    {
        if (filled($invoice->amount_paid)) {
            return round(((int) $invoice->amount_paid) / 100, 2);
        }

        if (filled($invoice->total)) {
            return round(((int) $invoice->total) / 100, 2);
        }

        return (float) $subscription->monthly_total;
    }

    /**
     * @param  Collection<int, ServicePlanSubscriptionPayment>  $payments
     * @return array<int, array{year: int, months: array<int, array<string, mixed>|null>}>
     */
    private function groupPaymentsByYear(Collection $payments): array
    {
        if ($payments->isEmpty()) {
            return [];
        }

        $startYear = (int) $payments->min('billing_year');
        $endYear = (int) $payments->max('billing_year');
        $grouped = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $months = array_fill(1, 12, null);

            $payments->where('billing_year', $year)->each(function (ServicePlanSubscriptionPayment $payment) use (&$months) {
                $months[$payment->billing_month] = $payment->toSummaryArray();
            });

            $grouped[] = [
                'year' => $year,
                'months' => $months,
            ];
        }

        return $grouped;
    }
}
