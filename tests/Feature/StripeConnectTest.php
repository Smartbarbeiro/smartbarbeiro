<?php

namespace Tests\Feature;

use App\Models\BarbershopServicePackage;
use App\Models\User;
use App\Services\StripeConnectService;
use App\Services\StripeServicePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
            'stripe.connect_enabled' => true,
            'stripe.application_fee_percent' => 10,
        ]);
    }

    public function test_public_profile_hides_checkout_until_connect_is_ready(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stripeConfigured', false));

        $barbershop->forceFill([
            'stripe_connect_account_id' => 'acct_ready',
            'stripe_connect_charges_enabled' => true,
        ])->save();

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stripeConfigured', true));
    }

    public function test_barbershop_can_start_stripe_connect_onboarding(): void
    {
        $barbershop = User::factory()->create();

        $this->mock(StripeConnectService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('createOnboardingUrl')
                ->once()
                ->andReturn('https://connect.stripe.test/onboard');
        });

        $this->actingAs($barbershop)
            ->post(route('stripe-connect.start'))
            ->assertRedirect('https://connect.stripe.test/onboard');
    }

    public function test_checkout_requires_connect_when_enabled(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $this->mock(StripeServicePlanService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('acceptsPaymentsFor')->andReturn(false);
            $mock->shouldReceive('createWebCheckoutSession')->never();
        });

        $this->actingAs($customer)
            ->from(route('profile.public', $barbershop->username))
            ->post(route('service-plan.subscribe', $barbershop->username), [
                'package_type' => 'cut',
                'addon_ids' => [],
            ])
            ->assertRedirect(route('profile.public', $barbershop->username).'#pagamento')
            ->assertSessionHasErrors('checkout');
    }

    public function test_accepts_payments_for_ready_connect_account(): void
    {
        $barbershop = User::factory()->stripeConnectReady()->create();
        $stripe = app(StripeServicePlanService::class);

        $this->assertTrue($stripe->acceptsPaymentsFor($barbershop));
        $this->assertTrue($barbershop->isStripeConnectReady());
    }

    public function test_profile_edit_exposes_stripe_connect_status(): void
    {
        $barbershop = User::factory()->stripeConnectReady()->create();

        $this->actingAs($barbershop)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stripeConnectEnabled', true)
                ->where('stripeConnect.ready', true)
                ->where('stripeConnect.account_id', $barbershop->stripe_connect_account_id));
    }
}
