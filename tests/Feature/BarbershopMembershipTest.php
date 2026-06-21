<?php

namespace Tests\Feature;

use App\Models\BarbershopMembership;
use App\Models\ProfileSubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class BarbershopMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_sign_up_prompt_on_public_profile(): void
    {
        $barbershop = User::factory()->create();

        $this->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Public')
                ->where('hasSignedUp', false)
                ->where('requiresPayment', false));
    }

    public function test_authenticated_user_can_sign_up_at_free_barbershop(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('barbershop.signup', $barbershop->username))
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHas('status', 'barbershop-signup-success');

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);
    }

    public function test_signed_up_user_sees_confirmation_on_profile(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hasSignedUp', true));
    }

    public function test_barbershop_owner_cannot_sign_up_at_own_profile(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->post(route('barbershop.signup', $barbershop->username))
            ->assertRedirect(route('profile.public', $barbershop->username));

        $this->assertDatabaseCount('barbershop_memberships', 0);
    }

    public function test_paid_barbershop_requires_subscription_flow_for_signup(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $barbershop->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        $this->actingAs($customer)
            ->post(route('barbershop.signup', $barbershop->username))
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHasErrors('signup');

        $this->assertDatabaseCount('barbershop_memberships', 0);
    }

    public function test_registration_from_barbershop_creates_customer_without_public_profile(): void
    {
        $barbershop = User::factory()->create();

        $this->post(route('register'), [
            'name' => 'New Customer',
            'cpf' => TestTaxDocuments::CPF,
            'email' => 'customer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'redirect' => '/barbearias/'.$barbershop->username,
        ])->assertRedirect('/barbearias/'.$barbershop->username);

        $customer = User::query()->where('email', 'customer@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertFalse($customer->isBarbershop());
        $this->assertNull($customer->username);
        $this->assertNull($customer->profileUrl());

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);
    }

    public function test_customer_does_not_have_public_profile_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isBarbershop', false)
                ->where('profileUrl', null));
    }

    public function test_customer_dashboard_redirects_to_barbershop_profile(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('profile.public', $barbershop->username));
    }

    public function test_customer_login_redirects_to_barbershop_profile(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect(route('profile.public', $barbershop->username));
    }

    public function test_normal_registration_creates_barbershop_owner_with_public_profile(): void
    {
        $this->post(route('register'), [
            'name' => 'Shop Owner',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'barbearia-do-owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $owner = User::query()->where('email', 'owner@example.com')->first();

        $this->assertNotNull($owner);
        $this->assertTrue($owner->isBarbershop());
        $this->assertNotNull($owner->username);
        $this->assertNotNull($owner->profileUrl());

        $this->get(route('profile.public', $owner->username))->assertOk();
    }
}
