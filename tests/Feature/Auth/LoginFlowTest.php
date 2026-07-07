<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershop_owner_can_reach_dashboard_after_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_login_with_redirect_query_in_form_body_still_reaches_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => '/registrar',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_with_barbershop_redirect_goes_to_profile(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
            'redirect' => '/barbearias/'.$barbershop->username,
        ])->assertRedirect('/barbearias/'.$barbershop->username);
    }
}
