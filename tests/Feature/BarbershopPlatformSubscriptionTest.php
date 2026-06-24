<?php

namespace Tests\Feature;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use App\Services\BarbershopPlatformPlanService;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Resources\PreApproval;
use MercadoPago\Resources\PreApprovalPlan;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class BarbershopPlatformSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_shows_platform_plan_price(): void
    {
        $this->get('/registrar')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Register')
                ->has('platformPlan')
                ->where('platformPlan.title', 'Plano Único')
                ->where('platformPlan.formatted_price', 'R$ 49,90'));
    }

    public function test_barbershop_registration_redirects_to_celebration_then_platform_subscribe(): void
    {
        $response = $this->post('/registrar', [
            'name' => 'Nova Barbearia',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'nova-barbearia',
            'email' => 'nova@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('register.celebration', absolute: false));

        $user = User::query()->where('email', 'nova@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('barbershop_platform_subscriptions', [
            'barbershop_user_id' => $user->id,
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->get(route('register.celebration'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('redirectTo', route('platform.subscribe', absolute: false)));

        $this->get(route('dashboard'))
            ->assertRedirect(route('platform.subscribe', absolute: false));
    }

    public function test_platform_checkout_rejects_test_email_when_collector_is_real(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['normal'],
            ]),
        ]);

        $barbershop = User::factory()->create(['email' => 'buyer@testuser.com']);
        $barbershop->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->actingAs($barbershop)
            ->from(route('platform.subscribe'))
            ->post(route('platform.subscribe.store'))
            ->assertRedirect(route('platform.subscribe'))
            ->assertSessionHasErrors([
                'subscribe' => __('messages.mercadopago_real_buyer_required'),
            ]);
    }

    public function test_platform_checkout_rejects_real_email_when_collector_is_test_seller(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['test_user'],
            ]),
        ]);

        $barbershop = User::factory()->create(['email' => 'real@gmail.com']);
        $barbershop->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->actingAs($barbershop)
            ->from(route('platform.subscribe'))
            ->post(route('platform.subscribe.store'))
            ->assertRedirect(route('platform.subscribe'))
            ->assertSessionHasErrors([
                'subscribe' => __('messages.mercadopago_test_buyer_required'),
            ]);
    }

    public function test_barbershop_can_start_platform_checkout(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        $barbershop = User::factory()->create();
        $barbershop->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $preapproval = new PreApproval;
        $preapproval->id = 'mp-platform-1';
        $preapproval->status = 'pending';
        $preapproval->init_point = 'https://mercadopago.test/platform-checkout';

        $this->mock(MercadoPagoService::class, function ($mock) use ($preapproval) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('assertSandboxCheckoutUsers')->andReturnNull();
            $mock->shouldReceive('createSubscriptionCheckout')->once()->andReturn($preapproval);
            $mock->shouldReceive('mapPreApprovalStatus')->andReturn('pending');
            $mock->shouldReceive('checkoutUrl')->andReturn('https://mercadopago.test/platform-checkout');
        });

        $this->actingAs($barbershop)
            ->post(route('platform.subscribe.store'))
            ->assertRedirect('https://mercadopago.test/platform-checkout');

        $this->assertDatabaseHas('barbershop_platform_subscriptions', [
            'barbershop_user_id' => $barbershop->id,
            'mercadopago_preapproval_id' => 'mp-platform-1',
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_update_platform_plan_price(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        $admin = User::factory()->admin()->create();
        $plan = BarbershopPlatformPlan::current();

        $mpPlan = new PreApprovalPlan;
        $mpPlan->id = 'mp-plan-platform';

        $this->mock(BarbershopPlatformPlanService::class, function ($mock) use ($plan, $mpPlan) {
            $mock->shouldReceive('update')
                ->once()
                ->andReturnUsing(function ($planModel, $data) use ($plan, $mpPlan) {
                    $planModel->fill([
                        'title' => $data['title'],
                        'description' => $data['description'] ?? null,
                        'monthly_amount' => $data['monthly_amount'],
                        'is_active' => (bool) ($data['is_active'] ?? true),
                        'mercadopago_preapproval_plan_id' => $mpPlan->id,
                    ]);
                    $planModel->save();

                    return $planModel;
                });
        });

        $this->actingAs($admin)
            ->patch(route('admin.platform-plan.update'), [
                'title' => 'Plano Premium',
                'description' => 'Acesso completo',
                'monthly_amount' => 79.9,
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'platform-plan-updated');

        $plan->refresh();
        $this->assertSame('Plano Premium', $plan->title);
        $this->assertEquals(79.9, (float) $plan->monthly_amount);
    }

    public function test_non_admin_cannot_update_platform_plan(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->patch(route('admin.platform-plan.update'), [
                'title' => 'Hack',
                'monthly_amount' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_unsubscribed_barbershop_public_profile_returns_404(): void
    {
        $barbershop = User::factory()->create();
        $barbershop->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertNotFound();
    }
}
