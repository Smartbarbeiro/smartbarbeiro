<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
        ]);
    }

    public function test_login_page_exposes_google_oauth_when_configured(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('oauthGoogleEnabled', true));
    }

    public function test_existing_user_can_login_via_google_callback(): void
    {
        $user = User::factory()->create([
            'email' => 'oauth-user@example.com',
        ]);

        $this->mockSocialiteUser('google-123', 'oauth-user@example.com', 'OAuth User');

        $response = $this->withSession(['oauth.intent' => 'login'])
            ->get(route('auth.social.callback', ['provider' => 'google']));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertSame('google', $user->oauth_provider);
        $this->assertSame('google-123', $user->oauth_id);
    }

    public function test_google_register_intent_redirects_to_complete_registration_for_new_users(): void
    {
        $this->mockSocialiteUser('google-new', 'new-oauth@example.com', 'New OAuth User');

        $response = $this->withSession(['oauth.intent' => 'register'])
            ->get(route('auth.social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('register.oauth.complete', absolute: false));
        $this->assertGuest();

        $this->get(route('register.oauth.complete'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/OAuthCompleteRegistration')
                ->where('oauthUser.email', 'new-oauth@example.com'));
    }

    public function test_oauth_registration_creates_barbershop_account(): void
    {
        $this->withSession([
            'oauth.registration' => [
                'provider' => 'google',
                'provider_id' => 'google-barbershop',
                'name' => 'Barbearia OAuth',
                'email' => 'barbearia-oauth@example.com',
                'redirect' => null,
                'is_customer' => false,
            ],
        ])->post(route('register.oauth.complete.store'), [
            'name' => 'Barbearia OAuth',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'barbearia-oauth',
        ])->assertRedirect(route('register.celebration', absolute: false));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'barbearia-oauth@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isBarbershopAccount());
        $this->assertNull($user->password);
        $this->assertSame('google', $user->oauth_provider);
    }

    public function test_password_login_is_blocked_for_oauth_only_accounts(): void
    {
        User::factory()->create([
            'email' => 'oauth-only@example.com',
            'password' => null,
            'oauth_provider' => 'google',
            'oauth_id' => 'google-only',
        ]);

        $this->post('/login', [
            'email' => 'oauth-only@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_google_register_redirects_with_register_specific_error(): void
    {
        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth failed'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->withSession(['oauth.intent' => 'register'])
            ->get(route('auth.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('register', absolute: false))
            ->assertSessionHasErrors([
                'oauth_google' => __('auth.oauth_register_failed'),
            ]);

        $this->assertGuest();
    }

    public function test_register_page_receives_google_oauth_error(): void
    {
        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth failed'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->withSession(['oauth.intent' => 'register'])
            ->get(route('auth.social.callback', ['provider' => 'google']));

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('errors.oauth_google', __('auth.oauth_register_failed')));
    }

    private function mockSocialiteUser(string $id, string $email, string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
