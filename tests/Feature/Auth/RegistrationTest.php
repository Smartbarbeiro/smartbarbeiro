<?php

namespace Tests\Feature\Auth;

use App\Mail\BarbershopWelcomeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        $response->assertRedirect(route('register.celebration', absolute: false));

        $this->get(route('register.celebration'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Register')
                ->where('celebrateRegistration', true)
                ->where('redirectTo', route('platform.subscribe', absolute: false))
            );
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

    public function test_barbershop_registration_sends_welcome_email_with_next_steps(): void
    {
        Mail::fake();

        $this->post('/registrar', [
            'name' => 'Barbearia Centro',
            'cpf_cnpj' => TestTaxDocuments::CNPJ,
            'username' => 'barbearia-centro',
            'email' => 'centro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Mail::assertSent(BarbershopWelcomeMail::class, function (BarbershopWelcomeMail $mail) {
            return $mail->hasTo('centro@example.com')
                && $mail->barbershop->username === 'barbearia-centro'
                && $mail->envelope()->subject === 'Bem-vindo ao '.config('app.name').' — próximos passos';
        });
    }

    public function test_customer_registration_does_not_send_barbershop_welcome_email(): void
    {
        Mail::fake();

        $barbershop = \App\Models\User::factory()->create([
            'username' => 'barbearia-demo',
        ]);

        $this->post('/registrar?redirect='.urlencode('/barbearias/'.$barbershop->username), [
            'name' => 'Cliente Novo',
            'cpf' => TestTaxDocuments::CPF,
            'email' => 'cliente@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Mail::assertNotSent(BarbershopWelcomeMail::class);
    }
}
