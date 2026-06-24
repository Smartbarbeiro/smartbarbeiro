<?php

namespace Tests\Feature\Api;

use App\Models\BarbershopServicePackage;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use MercadoPago\Resources\PreApproval;
use Tests\TestCase;

class ServicePlanNativeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_native_checkout_authorizes_subscription_with_card_token(): void
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
            'mercadopago.access_token' => 'TEST-fake-token',
            'mercadopago.public_key' => 'TEST-public-key',
        ]);

        $preapproval = new PreApproval;
        $preapproval->id = 'mp-preapproval-native';
        $preapproval->status = 'authorized';

        $this->mock(MercadoPagoService::class, function ($mock) use ($preapproval) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('assertSandboxCheckoutUsers')->andReturnNull();
            $mock->shouldReceive('createAuthorizedSubscription')->once()->andReturn($preapproval);
            $mock->shouldReceive('mapPreApprovalStatus')->andReturn(ServicePlanSubscription::STATUS_AUTHORIZED);
        });

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/barbearias/'.$barbershop->username.'/service-plans/checkout', [
            'package_type' => 'cut',
            'addon_ids' => [],
            'payment' => [
                'type' => 'card_token',
                'card_token_id' => 'e3ed6f098462036dd2cbabe314b9de2a',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', ServicePlanSubscription::STATUS_AUTHORIZED)
            ->assertJsonPath('checkout_url', null);

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $client->id,
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
            'mercadopago_preapproval_id' => 'mp-preapproval-native',
        ]);
    }

    public function test_barbershop_profile_exposes_payment_config_when_configured(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-pay-config',
            'is_frozen' => false,
        ]);

        config([
            'mercadopago.access_token' => 'TEST-fake-token',
            'mercadopago.public_key' => 'TEST-public-key',
            'mercadopago.merchant_name' => 'Smart Barbeiro',
        ]);

        $this->getJson('/api/v1/barbearias/'.$barbershop->username)
            ->assertOk()
            ->assertJsonPath('mercadopago_configured', true)
            ->assertJsonPath('payment_config.public_key', 'TEST-public-key')
            ->assertJsonPath('payment_config.merchant_name', 'Smart Barbeiro');
    }

    public function test_native_checkout_rejects_invalid_wallet_token(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-wallet-invalid',
            'is_frozen' => false,
        ]);

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $client = User::factory()->customer()->create();

        config(['mercadopago.access_token' => 'TEST-fake-token']);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/barbearias/'.$barbershop->username.'/service-plans/checkout', [
            'package_type' => 'cut',
            'addon_ids' => [],
            'payment' => [
                'type' => 'wallet',
                'wallet_type' => 'google_pay',
                'wallet_token' => '{"invalid":true}',
            ],
        ])->assertStatus(422)
            ->assertJsonPath('message', __('messages.wallet_token_invalid'));
    }
}
