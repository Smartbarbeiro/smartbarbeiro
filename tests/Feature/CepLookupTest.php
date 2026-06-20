<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\Cep\Models\Cep;
use Tests\TestCase;

class CepLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_lookup_cep(): void
    {
        $this->get(route('cep.lookup', ['postalCode' => '79002-000']))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_lookup_cep_from_database(): void
    {
        Cep::query()->create([
            'cep' => '79002000',
            'state' => 'MS',
            'city' => 'Campo Grande',
            'neighborhood' => 'Centro',
            'street' => 'Rua Example',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cep.lookup', ['postalCode' => '79002-000']))
            ->assertOk()
            ->assertJson([
                'postal_code' => '79002-000',
                'street' => 'Rua Example',
                'neighborhood' => 'Centro',
                'city' => 'Campo Grande',
                'state' => 'MS',
            ]);
    }

    public function test_lookup_returns_not_found_for_unknown_cep(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cep.lookup', ['postalCode' => '00000-000']))
            ->assertNotFound()
            ->assertJson([
                'message' => __('messages.cep_not_found'),
            ]);
    }
}
