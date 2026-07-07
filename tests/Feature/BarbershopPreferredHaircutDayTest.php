<?php

namespace Tests\Feature;

use App\Models\BarbershopMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopPreferredHaircutDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_up_client_is_prompted_to_choose_preferred_haircut_day(): void
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
            ->assertInertia(fn ($page) => $page
                ->where('hasSignedUp', true)
                ->where('needsPreferredHaircutDay', true)
                ->where('preferredHaircutDay', null));
    }

    public function test_client_can_save_preferred_haircut_day_after_signup(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->patch(route('barbershop.preferred-haircut-day.update', $barbershop->username), [
                'preferred_haircut_day' => 15,
            ])
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHas('status', 'preferred-haircut-day-saved');

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
            'preferred_haircut_day' => 15,
        ]);
    }

    public function test_client_is_not_prompted_after_saving_preferred_haircut_day(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
            'preferred_haircut_day' => 8,
        ]);

        $this->actingAs($customer)
            ->get(route('profile.public', $barbershop->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('needsPreferredHaircutDay', false)
                ->where('preferredHaircutDay', 8));
    }

    public function test_client_can_update_preferred_haircut_day(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
            'preferred_haircut_day' => 8,
        ]);

        $this->actingAs($customer)
            ->patch(route('barbershop.preferred-haircut-day.update', $barbershop->username), [
                'preferred_haircut_day' => 22,
            ])
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHas('status', 'preferred-haircut-day-saved');

        $this->assertDatabaseHas('barbershop_memberships', [
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
            'preferred_haircut_day' => 22,
        ]);
    }

    public function test_preferred_haircut_day_must_be_between_1_and_31(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->from(route('profile.public', $barbershop->username))
            ->patch(route('barbershop.preferred-haircut-day.update', $barbershop->username), [
                'preferred_haircut_day' => 32,
            ])
            ->assertRedirect(route('profile.public', $barbershop->username))
            ->assertSessionHasErrors('preferred_haircut_day');
    }

    public function test_barbershop_owner_cannot_set_preferred_haircut_day(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->patch(route('barbershop.preferred-haircut-day.update', $barbershop->username), [
                'preferred_haircut_day' => 10,
            ])
            ->assertForbidden();
    }
}
