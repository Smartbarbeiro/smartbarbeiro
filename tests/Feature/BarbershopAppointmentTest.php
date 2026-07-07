<?php

namespace Tests\Feature;

use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Models\BarbershopMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BarbershopAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_appointment(): void
    {
        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $scheduledAt = now()->addDay()->setTime(10, 0)->seconds(0);

        $this->actingAs($client)
            ->post(route('barbershop.appointments.store', $barbershop->username), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-requested');

        $this->assertDatabaseHas('barbershop_appointments', [
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'status' => BarbershopAppointment::STATUS_PENDING,
        ]);
    }

    public function test_barbershop_can_view_agenda_and_confirm_with_employee(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Pedro',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->setTime(11, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'package_type' => 'cut',
            'status' => BarbershopAppointment::STATUS_PENDING,
        ]);

        $this->actingAs($barbershop)
            ->get(route('agenda.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->has('agenda.slots', 24)
                ->where('agenda.date', '2026-07-07'));

        $this->actingAs($barbershop)
            ->patch(route('agenda.update', $appointment), [
                'action' => 'confirm',
                'barbershop_employee_id' => $employee->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-updated');

        $this->assertDatabaseHas('barbershop_appointments', [
            'id' => $appointment->id,
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
            'barbershop_employee_id' => $employee->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_availability_endpoint_hides_taken_slots(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->setTime(10, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        $response = $this->getJson(route('barbershop.appointments.availability', [
            'username' => $barbershop->username,
            'date' => '2026-07-07',
        ]));

        $response->assertOk();

        $tenAm = collect($response->json('slots'))->firstWhere('time', '10:00');
        $elevenAm = collect($response->json('slots'))->firstWhere('time', '11:00');

        $this->assertFalse($tenAm['is_available']);
        $this->assertTrue($elevenAm['is_available']);

        Carbon::setTestNow();
    }
}
