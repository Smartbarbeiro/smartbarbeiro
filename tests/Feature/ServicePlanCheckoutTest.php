<?php

namespace Tests\Feature;

use App\Models\BarbershopServiceAddon;
use App\Models\BarbershopServicePackage;
use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\StripeServicePlanService;
use App\Services\ServicePlanSubscriptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Subscription;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class ServicePlanCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_start_service_plan_checkout(): void
    {
        $barbershop = User::factory()->create();

        $this->post(route('service-plan.subscribe', $barbershop->username), [
            'package_type' => 'cut',
            'addon_ids' => [],
        ])->assertRedirect(route('login'));
    }

    public function test_guest_can_register_and_start_service_plan_checkout(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
        ]);

        $this->mock(StripeServicePlanService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('findOrCreateCustomer')->andReturn('cus_test');
            $mock->shouldReceive('createWebCheckoutSession')->once()->andReturn('https://stripe.test/checkout-guest');
        });

        $this->from(route('profile.public', $barbershop->username))
            ->post(route('service-plan.subscribe.register', $barbershop->username), [
                'name' => 'Cliente Novo',
                'cpf' => TestTaxDocuments::CPF,
                'email' => 'cliente@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'package_type' => 'cut',
                'addon_ids' => [],
            ])
            ->assertRedirect('https://stripe.test/checkout-guest');

        $customer = User::query()->where('email', 'cliente@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertFalse($customer->is_barbershop);
        $this->assertAuthenticatedAs($customer);

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'status' => ServicePlanSubscription::STATUS_PENDING,
        ]);
    }

    public function test_guest_register_checkout_validates_registration_fields(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        config(['stripe.secret' => 'TEST-fake-token']);

        $this->from(route('profile.public', $barbershop->username))
            ->post(route('service-plan.subscribe.register', $barbershop->username), [
                'name' => '',
                'cpf' => '123',
                'email' => 'invalid-email',
                'password' => 'short',
                'password_confirmation' => 'other',
                'package_type' => 'cut',
                'addon_ids' => [],
            ])
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHasErrors(['name', 'cpf', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_guest_can_register_without_stripe_and_save_pending_plan(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        config(['stripe.secret' => null, 'stripe.key' => null]);

        $this->from(route('profile.public', $barbershop->username))
            ->post(route('service-plan.subscribe.register', $barbershop->username), [
                'name' => 'Cliente Sem Pagamento',
                'cpf' => '981.366.228-09',
                'email' => 'sem-pagamento@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'package_type' => 'cut',
                'addon_ids' => [],
            ])
            ->assertRedirect(route('profile.public', $barbershop->username).'#pagamento')
            ->assertSessionHas('status', 'service-plan-signup-pending');

        $customer = User::query()->where('email', 'sem-pagamento@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertAuthenticatedAs($customer);

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'status' => ServicePlanSubscription::STATUS_PENDING,
            'stripe_subscription_id' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasSignedUp', true)
                ->where('hasActiveServicePlanSubscription', false)
                ->where('pendingServicePlanSubscription.package_type', 'cut'));
    }

    public function test_checkout_requires_valid_package(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        config(['stripe.secret' => 'TEST-fake-token']);

        $this->actingAs($customer)
            ->from(route('profile.public', $barbershop->username))
            ->post(route('service-plan.subscribe', $barbershop->username), [
                'package_type' => 'cut',
                'addon_ids' => [],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('checkout');
    }

    public function test_customer_can_start_checkout_with_package_and_addons(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $addon = BarbershopServiceAddon::create([
            'user_id' => $barbershop->id,
            'name' => 'Sobrancelha',
            'monthly_price' => 15,
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
        ]);

        $this->mock(StripeServicePlanService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('findOrCreateCustomer')->andReturn('cus_test');
            $mock->shouldReceive('createWebCheckoutSession')->once()->andReturn('https://stripe.test/checkout');
        });

        $this->actingAs($customer)
            ->post(route('service-plan.subscribe', $barbershop->username), [
                'package_type' => 'cut',
                'addon_ids' => [$addon->id],
            ])
            ->assertRedirect('https://stripe.test/checkout');

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'monthly_total' => 114,
            'status' => ServicePlanSubscription::STATUS_PENDING,
            'stripe_subscription_id' => null,
        ]);
    }

    public function test_authorized_service_plan_creates_barbershop_membership_on_sync(): void
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
            'external_reference' => 'service-plan-test-ref',
            'status' => ServicePlanSubscription::STATUS_PENDING,
            'stripe_subscription_id' => 'sub_test_sync',
            'status' => ServicePlanSubscription::STATUS_PENDING,
        ]);

        $stripeSubscription = Subscription::constructFrom([
            'id' => 'sub_test_sync',
            'status' => 'active',
            'metadata' => [
                'service_plan_subscription_id' => (string) $subscription->id,
                'external_reference' => 'service-plan-test-ref',
            ],
            'current_period_end' => now()->addMonth()->timestamp,
        ]);

        $this->mock(StripeServicePlanService::class, function ($mock) use ($stripeSubscription) {
            $mock->shouldReceive('mapSubscriptionStatus')->andReturn(ServicePlanSubscription::STATUS_AUTHORIZED);
        });

        app(ServicePlanSubscriptionSyncService::class)->syncFromStripeSubscription($stripeSubscription);

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'id' => $subscription->id,
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);
    }

    public function test_subscriber_can_cancel_service_plan_subscription(): void
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
            'external_reference' => 'service-plan-cancel-ref',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($customer)
            ->delete(route('service-plan-subscriptions.destroy', $subscription))
            ->assertRedirect()
            ->assertSessionHas('status', 'subscription-cancelled');

        $this->assertDatabaseHas('service_plan_subscriptions', [
            'id' => $subscription->id,
            'status' => ServicePlanSubscription::STATUS_CANCELLED,
        ]);
    }

    public function test_public_profile_marks_signed_up_with_active_service_plan(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customer->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $customer->email,
            'external_reference' => 'service-plan-public-ref',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($customer)
            ->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasSignedUp', true)
                ->where('hasActiveServicePlanSubscription', true));
    }
}
