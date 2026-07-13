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

        $plan = \App\Models\BarbershopPlatformPlan::current();
        $plan->update(['mercadopago_preapproval_plan_id' => 'mp-plan-flow']);

        $mpPlan = new \MercadoPago\Resources\PreApprovalPlan;
        $mpPlan->id = 'mp-plan-flow';
        $mpPlan->init_point = 'https://mercadopago.test/checkout-flow';

        $this->mock(MercadoPagoService::class, function ($mock) use ($mpPlan) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('assertSandboxCheckoutUsers')->andReturnNull();
            $mock->shouldReceive('updatePreApprovalPlan')->once()->andReturn($mpPlan);
            $mock->shouldReceive('getPreApprovalPlan')->once()->andReturn($mpPlan);
            $mock->shouldReceive('planCheckoutUrl')->once()->andReturn('https://mercadopago.test/checkout-flow');
            $mock->shouldReceive('mapPreApprovalStatus')
                ->andReturnUsing(fn (?string $status) => match ($status) {
                    'authorized', 'active' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
                    default => BarbershopPlatformSubscription::STATUS_PENDING,
                });
            $mock->shouldReceive('getPreApproval')
                ->andReturnUsing(function () {
                    $authorized = new PreApproval;
                    $authorized->id = 'mp-flow-preapproval';
                    $authorized->status = 'authorized';
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
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->get(route('platform.subscribe.return', ['preapproval_id' => 'mp-flow-preapproval']))
            ->assertRedirect(route('profile.edit', absolute: false))
            ->assertSessionHas('status', 'platform-subscription-active')
            ->assertSessionHas('prompt_profile_photo', true);

        $this->actingAs($user->fresh())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->where('promptProfilePhoto', true));

        $user->refresh();
        $this->assertDatabaseHas('barbershop_platform_subscriptions', [
            'barbershop_user_id' => $user->id,
            'mercadopago_preapproval_id' => 'mp-flow-preapproval',
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
