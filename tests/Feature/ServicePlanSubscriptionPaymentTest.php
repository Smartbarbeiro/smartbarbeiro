<?php

namespace Tests\Feature;

use App\Models\ServicePlanSubscription;
use App\Models\ServicePlanSubscriptionPayment;
use App\Models\User;
use App\Services\ServicePlanSubscriptionPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePlanSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_index_includes_monthly_payment_history(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $subscription = ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $customer->email,
            'external_reference' => 'service-plan-history-test',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        app(ServicePlanSubscriptionPaymentService::class)->syncSchedule($subscription);

        ServicePlanSubscriptionPayment::query()
            ->where('service_plan_subscription_id', $subscription->id)
            ->where('billing_year', now()->year)
            ->where('billing_month', now()->month)
            ->update([
                'status' => ServicePlanSubscriptionPayment::STATUS_PAID,
                'paid_at' => now(),
            ]);

        $this->actingAs($customer)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('subscriptions', 1)
                ->where('subscriptions.0.kind', 'service_plan')
                ->has('subscriptions.0.payment_history', 1));
    }

    public function test_customer_can_download_paid_payment_nota_fiscal_pdf(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create([
            'tax_document' => '390.533.447-05',
        ]);

        $subscription = ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $customer->email,
            'external_reference' => 'service-plan-nf-test',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $payment = ServicePlanSubscriptionPayment::create([
            'service_plan_subscription_id' => $subscription->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'status' => ServicePlanSubscriptionPayment::STATUS_PAID,
            'amount' => 99,
            'currency_id' => 'BRL',
            'paid_at' => now(),
            'stripe_invoice_id' => 'in_test_123',
        ]);

        $this->actingAs($customer)
            ->get(route('service-plan-payments.nota-fiscal', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_cannot_download_pending_payment_nota_fiscal(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();

        $subscription = ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $customer->email,
            'external_reference' => 'service-plan-nf-deny',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $payment = ServicePlanSubscriptionPayment::create([
            'service_plan_subscription_id' => $subscription->id,
            'billing_year' => now()->year,
            'billing_month' => now()->month,
            'status' => ServicePlanSubscriptionPayment::STATUS_PENDING,
            'amount' => 99,
            'currency_id' => 'BRL',
        ]);

        $this->actingAs($otherCustomer)
            ->get(route('service-plan-payments.nota-fiscal', $payment))
            ->assertForbidden();
    }
}
