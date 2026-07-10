<?php

namespace Tests\Feature\Api;

use App\Models\BarbershopServicePackage;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\StripeServicePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicePlanNativeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_checkout_returns_stripe_payment_sheet_payload(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-wallet',
            'is_frozen' => false,
        ]);

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $client = User::factory()->customer()->create();

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
        ]);

        $this->mock(StripeServicePlanService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('acceptsPaymentsFor')->andReturn(true);
            $mock->shouldReceive('prepareMobileSubscription')->once()->andReturn([
                'customer_id' => 'cus_test',
                'customer_ephemeral_key_secret' => 'ek_test',
                'payment_intent_client_secret' => 'pi_test_secret',
                'subscription_id' => 'sub_test',
            ]);
        });

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/barbearias/'.$barbershop->username.'/service-plans/checkout/prepare', [
            'package_type' => 'cut',
            'addon_ids' => [],
        ])
            ->assertOk()
            ->assertJsonPath('customer_id', 'cus_test')
            ->assertJsonPath('subscription_id', 'sub_test')
            ->assertJsonPath('status', 'requires_payment');
    }

    public function test_confirm_checkout_authorizes_subscription(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-confirm',
            'is_frozen' => false,
        ]);

        $client = User::factory()->customer()->create();

        $subscription = ServicePlanSubscription::query()->create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $client->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $client->email,
            'external_reference' => 'service-plan-confirm-test',
            'stripe_subscription_id' => 'sub_test',
            'status' => ServicePlanSubscription::STATUS_PENDING,
        ]);

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
        ]);

        $this->mock(StripeServicePlanService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('retrieveSubscription')->once()->andReturn(
                \Stripe\Subscription::constructFrom([
                    'id' => 'sub_test',
                    'status' => 'active',
                    'metadata' => [
                        'service_plan_subscription_id' => '1',
                    ],
                ])
            );
            $mock->shouldReceive('mapSubscriptionStatus')->andReturn(ServicePlanSubscription::STATUS_AUTHORIZED);
        });

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/barbearias/'.$barbershop->username.'/service-plans/checkout/confirm', [
            'subscription_id' => 'sub_test',
        ])
            ->assertOk()
            ->assertJsonPath('status', ServicePlanSubscription::STATUS_AUTHORIZED);
    }

    public function test_barbershop_profile_exposes_stripe_payment_config_when_configured(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-pay-config',
            'is_frozen' => false,
        ]);

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
            'stripe.merchant_display_name' => 'Smart Barbeiro',
        ]);

        $this->getJson('/api/v1/barbearias/'.$barbershop->username)
            ->assertOk()
            ->assertJsonPath('stripe_configured', true)
            ->assertJsonPath('payment_config.publishable_key', 'pk_test_fake')
            ->assertJsonPath('payment_config.merchant_display_name', 'Smart Barbeiro');
    }
}
