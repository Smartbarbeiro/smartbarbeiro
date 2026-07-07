<?php

namespace Tests\Feature;

use App\Models\BarbershopEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershop_can_view_employees_page(): void
    {
        $barbershop = User::factory()->create();

        BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'João Barbeiro',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($barbershop)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Employees/Index')
                ->has('employees', 1)
                ->where('employees.0.name', 'João Barbeiro')
                ->where('employees.0.commission_percent', 40));
    }

    public function test_barbershop_can_create_update_and_delete_employee(): void
    {
        $barbershop = User::factory()->create();
        $otherBarbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->post(route('employees.store'), [
                'name' => 'Carlos',
                'commission_percent' => 35,
                'color' => '#10B981',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'employee-created');

        $employee = BarbershopEmployee::query()->firstOrFail();

        $this->assertDatabaseHas('barbershop_employees', [
            'id' => $employee->id,
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Carlos',
            'commission_percent' => 35,
            'color' => '#10B981',
        ]);

        $this->actingAs($barbershop)
            ->patch(route('employees.update', $employee), [
                'name' => 'Carlos Silva',
                'commission_percent' => 50,
                'color' => '#EF4444',
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'employee-updated');

        $this->assertDatabaseHas('barbershop_employees', [
            'id' => $employee->id,
            'name' => 'Carlos Silva',
            'commission_percent' => 50,
            'color' => '#EF4444',
            'is_active' => false,
        ]);

        $this->actingAs($otherBarbershop)
            ->patch(route('employees.update', $employee), [
                'name' => 'Hack',
                'commission_percent' => 10,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($barbershop)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect()
            ->assertSessionHas('status', 'employee-deleted');

        $this->assertDatabaseMissing('barbershop_employees', [
            'id' => $employee->id,
        ]);
    }

    public function test_customer_cannot_manage_employees(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('employees.store'), [
                'name' => 'Carlos',
                'commission_percent' => 35,
            ])
            ->assertForbidden();
    }
}
