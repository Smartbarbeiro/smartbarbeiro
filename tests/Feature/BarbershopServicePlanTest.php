<?php

namespace Tests\Feature;

use App\Models\BarbershopServiceAddon;
use App\Models\BarbershopServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopServicePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershop_gets_default_packages_on_creation(): void
    {
        $barbershop = User::factory()->create();

        $this->assertDatabaseHas('barbershop_service_packages', [
            'user_id' => $barbershop->id,
            'type' => BarbershopServicePackage::TYPE_CUT,
        ]);

        $this->assertDatabaseHas('barbershop_service_packages', [
            'user_id' => $barbershop->id,
            'type' => BarbershopServicePackage::TYPE_CUT_BEARD,
        ]);
    }

    public function test_has_configured_packages_is_false_until_price_is_set(): void
    {
        $barbershop = User::factory()->create();
        $service = app(\App\Services\BarbershopServicePlanService::class);

        $this->assertFalse($service->hasConfiguredPackages($barbershop));

        $barbershop->servicePackages()
            ->where('type', 'cut')
            ->update(['monthly_price' => 79.9]);

        $this->assertTrue($service->hasConfiguredPackages($barbershop->fresh()));
    }

    public function test_barbershop_can_update_service_plans_and_addons(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->put(route('profile.service-plans.update'), [
                'packages' => [
                    'cut' => [
                        'monthly_price' => 89.9,
                        'is_enabled' => true,
                    ],
                    'cut_beard' => [
                        'monthly_price' => 129.9,
                        'is_enabled' => true,
                    ],
                ],
                'addons' => [
                    [
                        'name' => 'Sobrancelha',
                        'monthly_price' => 15,
                        'is_enabled' => true,
                        'sort_order' => 0,
                    ],
                ],
                'deleted_addon_ids' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'service-plans-updated');

        $this->assertDatabaseHas('barbershop_service_packages', [
            'user_id' => $barbershop->id,
            'type' => BarbershopServicePackage::TYPE_CUT,
            'monthly_price' => 89.9,
        ]);

        $this->assertDatabaseHas('barbershop_service_addons', [
            'user_id' => $barbershop->id,
            'name' => 'Sobrancelha',
            'monthly_price' => 15,
        ]);
    }

    public function test_guest_can_see_service_plan_prices_without_signup(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 79.9, 'is_enabled' => true]);

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT_BEARD)
            ->update(['monthly_price' => 119.9, 'is_enabled' => true]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('servicePlans.packages', 2)
                ->where('servicePlans.packages.0.formatted_price', 'R$ 79,90')
                ->where('servicePlans.packages.1.formatted_price', 'R$ 119,90'));
    }

    public function test_public_profile_exposes_configured_packages_and_addons(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 99, 'is_enabled' => true]);

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT_BEARD)
            ->update(['monthly_price' => 0, 'is_enabled' => false]);

        BarbershopServiceAddon::create([
            'user_id' => $barbershop->id,
            'name' => 'Hidratação',
            'monthly_price' => 20,
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Public')
                ->has('servicePlans.packages', 1)
                ->where('servicePlans.packages.0.type', 'cut')
                ->has('servicePlans.addons', 1)
                ->where('servicePlans.addons.0.name', 'Hidratação')
                ->has('mobileApp', fn ($mobileApp) => $mobileApp
                    ->has('name')
                    ->has('play_store_url')
                    ->has('app_store_url'))
                ->where('stripeConfigured', false));
    }

    public function test_public_profile_uses_stripe_for_service_plan_checkout(): void
    {
        $barbershop = User::factory()->create();

        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.key' => 'pk_test_fake',
            'mercadopago.access_token' => 'TEST-fake-token',
        ]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stripeConfigured', true)
                ->where('mercadopagoConfigured', true));
    }

    public function test_customer_cannot_update_service_plans(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('profile.service-plans.update'), [
                'packages' => [
                    'cut' => [
                        'monthly_price' => 50,
                        'is_enabled' => true,
                    ],
                    'cut_beard' => [
                        'monthly_price' => 70,
                        'is_enabled' => true,
                    ],
                ],
                'addons' => [],
                'deleted_addon_ids' => [],
            ])
            ->assertForbidden();
    }
}
