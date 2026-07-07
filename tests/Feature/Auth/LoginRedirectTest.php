<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_ignores_intended_register_url(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => '/register'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_ignores_intended_registrar_url(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => '/registrar'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_ignores_intended_registrar_url_with_trailing_slash(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => '/registrar/'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_authenticated_user_visiting_registrar_is_sent_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/registrar')
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
