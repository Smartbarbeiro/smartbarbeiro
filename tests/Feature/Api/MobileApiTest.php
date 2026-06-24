<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MobileApiTest extends TestCase
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

    public function test_client_can_login_and_fetch_barbershop_profile(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-teste',
            'is_frozen' => false,
        ]);

        $client = User::factory()->customer()->create();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $client->email,
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $profile = $this->getJson('/api/v1/barbearias/'.$barbershop->username);

        $profile->assertOk()
            ->assertJsonPath('profile.username', $barbershop->username)
            ->assertJsonStructure(['service_plans', 'mercadopago_configured']);
    }

    public function test_client_can_register_with_barbershop(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-nova',
            'is_frozen' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Cliente Novo',
            'cpf' => '529.982.247-25',
            'email' => 'cliente@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'barbershop_username' => $barbershop->username,
            'device_name' => 'test',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $response->json('user.id'),
        ]);
    }

    public function test_google_config_endpoint_exposes_client_id_when_enabled(): void
    {
        $this->getJson('/api/v1/auth/google/config')
            ->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('client_id', 'test-google-client-id');
    }

    public function test_existing_user_can_login_with_google_access_token(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'mobile-google@example.com',
            'oauth_provider' => 'google',
            'oauth_id' => 'google-mobile-1',
            'password' => null,
        ]);

        $this->mockGoogleAccessToken('google-mobile-1', 'mobile-google@example.com', 'Mobile Google');

        $response = $this->postJson('/api/v1/auth/google', [
            'access_token' => 'valid-access-token',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertSame($user->id, $response->json('user.id'));
    }

    public function test_unknown_google_user_returns_registration_required(): void
    {
        $this->mockGoogleAccessToken('google-new-mobile', 'new-mobile@example.com', 'New Mobile');

        $this->postJson('/api/v1/auth/google', [
            'access_token' => 'valid-access-token',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'registration_required')
            ->assertJsonPath('google_user.email', 'new-mobile@example.com');
    }

    public function test_client_can_register_with_google_at_barbershop(): void
    {
        $barbershop = User::factory()->create([
            'username' => 'barbearia-google',
            'is_frozen' => false,
        ]);

        $this->mockGoogleAccessToken('google-register-mobile', 'google-register@example.com', 'Google Register');

        $response = $this->postJson('/api/v1/auth/google/register', [
            'access_token' => 'valid-access-token',
            'name' => 'Google Register',
            'cpf' => '529.982.247-25',
            'barbershop_username' => $barbershop->username,
            'device_name' => 'test',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', [
            'email' => 'google-register@example.com',
            'oauth_provider' => 'google',
            'oauth_id' => 'google-register-mobile',
        ]);
    }

    private function mockGoogleAccessToken(string $id, string $email, string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('userFromToken')->with('valid-access-token')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
