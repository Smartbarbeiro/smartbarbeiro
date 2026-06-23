<?php

namespace Tests\Feature;

use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Resources\PreApproval;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class BarbershopSignupPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_signup_to_payment_return_unlocks_dashboard_and_public_profile(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        $preapproval = new PreApproval;
        $preapproval->id = 'mp-flow-preapproval';
        $preapproval->status = 'pending';
        $preapproval->init_point = 'https://mercadopago.test/checkout-flow';
        $preapproval->external_reference = 'will-be-set';

        $this->mock(MercadoPagoService::class, function ($mock) use ($preapproval) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('assertSandboxTestBuyer')->andReturnNull();
            $mock->shouldReceive('createSubscriptionCheckout')
                ->once()
                ->andReturn($preapproval);
            $mock->shouldReceive('mapPreApprovalStatus')
                ->andReturnUsing(fn (?string $status) => match ($status) {
                    'authorized', 'active' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
                    default => BarbershopPlatformSubscription::STATUS_PENDING,
                });
            $mock->shouldReceive('checkoutUrl')->andReturn('https://mercadopago.test/checkout-flow');
            $mock->shouldReceive('getPreApproval')
                ->andReturnUsing(function () use ($preapproval) {
                    $authorized = new PreApproval;
                    $authorized->id = $preapproval->id;
                    $authorized->status = 'authorized';
                    $authorized->external_reference = $preapproval->external_reference;
                    $authorized->payer_email = 'flow@example.com';

                    return $authorized;
                });
        });

        $this->post('/registrar', [
            'name' => 'Barbearia Flow',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'barbearia-flow',
            'email' => 'flow@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('register.celebration', absolute: false));

        $user = User::query()->where('email', 'flow@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $subscription = $user->platformSubscription;
        $this->assertNotNull($subscription);
        $this->assertSame(BarbershopPlatformSubscription::STATUS_PENDING, $subscription->status);

        $preapproval->external_reference = $subscription->external_reference;

        $this->get(route('register.celebration'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('redirectTo', route('platform.subscribe', absolute: false)));

        $this->get(route('platform.subscribe'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Subscribe')
                ->has('plan')
                ->where('paymentsConfigured', true));

        $this->post(route('platform.subscribe.store'))
            ->assertRedirect('https://mercadopago.test/checkout-flow');

        $this->assertDatabaseHas('barbershop_platform_subscriptions', [
            'barbershop_user_id' => $user->id,
            'mercadopago_preapproval_id' => 'mp-flow-preapproval',
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->get(route('platform.subscribe.return'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/SubscribeReturn')
                ->where('subscription.is_active', true));

        $user->refresh();
        $this->assertDatabaseHas('barbershop_platform_subscriptions', [
            'barbershop_user_id' => $user->id,
            'status' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user->fresh());
        $this->assertTrue($user->fresh()->hasActivePlatformSubscription());

        $this->get(route('dashboard'))->assertOk();

        $this->get(route('profile.public', $user->username))->assertOk();
    }

    public function test_signup_without_mercadopago_shows_subscribe_page_with_warning(): void
    {
        config(['mercadopago.access_token' => null]);

        $this->post('/registrar', [
            'name' => 'Barbearia Sem MP',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'barbearia-sem-mp',
            'email' => 'semmp@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('register.celebration', absolute: false));

        $user = User::query()->where('email', 'semmp@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('platform.subscribe'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paymentsConfigured', false));

        $this->actingAs($user)
            ->post(route('platform.subscribe.store'))
            ->assertRedirect()
            ->assertSessionHas('status', 'platform-subscription-pending');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.subscribe', absolute: false));
    }
}
