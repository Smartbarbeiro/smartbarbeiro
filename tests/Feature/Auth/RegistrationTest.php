<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestTaxDocuments;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/registrar');

        $response->assertStatus(200);
    }

    public function test_legacy_register_url_redirects_to_registrar(): void
    {
        $this->get('/register?redirect=%2Fbarbearias%2Fdemo')
            ->assertRedirect('/registrar?redirect=%2Fbarbearias%2Fdemo');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/registrar', [
            'name' => 'Test User',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'minha-barbearia',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_barbershop_registration_requires_username(): void
    {
        $this->post('/registrar', [
            'name' => 'Test User',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'email' => 'no-username@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_barbershop_registration_requires_valid_cpf_cnpj(): void
    {
        $this->post('/registrar', [
            'name' => 'Test User',
            'cpf_cnpj' => '123.456.789-00',
            'username' => 'minha-barbearia',
            'email' => 'invalid-doc@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('cpf_cnpj');

        $this->assertGuest();
    }
}
