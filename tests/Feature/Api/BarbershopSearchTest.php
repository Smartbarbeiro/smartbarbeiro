<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_at_least_three_characters(): void
    {
        $this->getJson('/api/v1/barbearias/search?q=ab')
            ->assertStatus(422);
    }

    public function test_search_returns_matching_public_barbershops(): void
    {
        User::factory()->create([
            'username' => 'barbearia-centro',
            'name' => 'Barbearia Centro',
            'is_frozen' => false,
        ]);

        User::factory()->create([
            'username' => 'corte-fino',
            'name' => 'Corte Fino',
            'is_frozen' => false,
        ]);

        $this->getJson('/api/v1/barbearias/search?q=bar')
            ->assertOk()
            ->assertJsonPath('profile_base_url', url('/barbearias/'))
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.username', 'barbearia-centro')
            ->assertJsonPath('results.0.name', 'Barbearia Centro');
    }

    public function test_search_matches_barbershop_name(): void
    {
        User::factory()->create([
            'username' => 'navalha-ouro',
            'name' => 'Navalha de Ouro',
            'is_frozen' => false,
        ]);

        $this->getJson('/api/v1/barbearias/search?q=nav')
            ->assertOk()
            ->assertJsonPath('results.0.username', 'navalha-ouro');
    }

    public function test_search_matches_username_with_spaces_as_underscores(): void
    {
        User::factory()->create([
            'username' => 'barbearia_do_joao',
            'name' => 'João Silva',
            'is_frozen' => false,
        ]);

        $this->getJson('/api/v1/barbearias/search?q=barbearia do')
            ->assertOk()
            ->assertJsonPath('results.0.username', 'barbearia_do_joao')
            ->assertJsonPath('results.0.name', 'João Silva');
    }
}
