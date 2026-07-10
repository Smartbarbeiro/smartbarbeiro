<?php

namespace Tests\Feature;

use App\Models\ProfileSubscription;
use App\Models\ProfileSubscriptionPlan;
use App\Models\User;
use App\Services\PaymentEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentEmailMismatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_detects_mismatch_between_account_and_payer_email(): void
    {
        $user = User::factory()->customer()->create(['email' => 'signup@example.com']);
        $creator = User::factory()->create();

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $user->id,
            'payer_email' => 'payer@mercadopago.com',
            'external_reference' => 'test-ref-mismatch',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $service = app(PaymentEmailService::class);

        $this->assertFalse($service->matchesAccount($user, 'payer@mercadopago.com'));
        $this->assertSame([
            'account_email' => 'signup@example.com',
            'payer_email' => 'payer@mercadopago.com',
            'billing_email' => null,
        ], $service->mismatchForUser($user));
    }

    public function test_saving_billing_email_clears_mismatch(): void
    {
        $user = User::factory()->customer()->create(['email' => 'signup@example.com']);
        $creator = User::factory()->create();

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $user->id,
            'payer_email' => 'payer@mercadopago.com',
            'external_reference' => 'test-ref-billing',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($user)
            ->post(route('profile.payment-email.update'), [
                'action' => 'billing',
                'payer_email' => 'payer@mercadopago.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'payment-email-billing-saved');

        $user->refresh();

        $this->assertSame('payer@mercadopago.com', $user->billing_email);
        $this->assertNull(app(PaymentEmailService::class)->mismatchForUser($user));
    }

    public function test_profile_subscribe_return_includes_mismatch_payload(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        $creator = User::factory()->create(['username' => 'barbearia-mp']);
        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'Plano',
            'description' => 'Desc',
            'monthly_amount' => 29.90,
            'currency_id' => 'BRL',
        ]);

        $subscriber = User::factory()->customer()->create(['email' => 'signup@example.com']);

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => 'payer@mercadopago.com',
            'mercadopago_preapproval_id' => 'mp-return-test',
            'external_reference' => 'test-ref-return',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->mock(\App\Services\MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('getPreApproval')->andReturnUsing(function () {
                $preapproval = new \MercadoPago\Resources\PreApproval;
                $preapproval->id = 'mp-return-test';
                $preapproval->status = 'authorized';
                $preapproval->payer_email = 'payer@mercadopago.com';

                return $preapproval;
            });
            $mock->shouldReceive('mapPreApprovalStatus')->andReturn(ProfileSubscription::STATUS_AUTHORIZED);
        });

        $this->actingAs($subscriber)
            ->get(route('profile.subscribe.return', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/SubscribeReturn')
                ->where('paymentEmailMismatch.account_email', 'signup@example.com')
                ->where('paymentEmailMismatch.payer_email', 'payer@mercadopago.com'));
    public function test_public_profile_shows_payment_email_mismatch_for_subscriber(): void
    {
        $creator = User::factory()->create();
        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        $subscriber = User::factory()->customer()->create(['email' => 'signup@example.com']);

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => 'payer@mercadopago.com',
            'external_reference' => 'test-ref-public',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($subscriber)
            ->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Public')
                ->where('paymentEmailMismatch.account_email', 'signup@example.com')
                ->where('paymentEmailMismatch.payer_email', 'payer@mercadopago.com'));
    }
}
