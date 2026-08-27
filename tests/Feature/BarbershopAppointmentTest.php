<?php

namespace Tests\Feature;

use App\Mail\BarbershopAppointmentRequestedMail;
use App\Mail\ClientAppointmentConfirmedMail;
use App\Mail\ClientAppointmentRejectedMail;
use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Models\BarbershopMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
            ->assertRedirect(route('client.appointments.index'))
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
            'color' => '#8B5CF6',
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

        $this->actingAs($barbershop)
            ->get(route('agenda.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('agenda.slots.6.appointments.0.employee.color', '#8B5CF6'));

        Carbon::setTestNow();
    }

    public function test_availability_endpoint_hides_taken_slots(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();
        $tomorrow = now()->addDay()->toDateString();

        BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($client)
            ->getJson(route('barbershop.appointments.availability', [
                'username' => $barbershop->username,
                'date' => $tomorrow,
            ]));

        $response->assertOk()
            ->assertJsonPath('date', $tomorrow);

        $slotTimes = collect($response->json('slots'))->pluck('time')->all();
        $tenAm = collect($response->json('slots'))->firstWhere('time', '10:00');
        $elevenAm = collect($response->json('slots'))->firstWhere('time', '11:00');

        $this->assertFalse($tenAm['is_available']);
        $this->assertTrue($elevenAm['is_available']);
        $this->assertContains('10:00', $slotTimes);
        $this->assertContains('11:00', $slotTimes);

        Carbon::setTestNow();
    }

    public function test_cancelled_appointment_shows_slot_as_available_on_agenda(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->setTime(11, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        $this->actingAs($barbershop)
            ->patch(route('agenda.update', $appointment), [
                'action' => 'cancel',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-updated');

        $this->actingAs($barbershop)
            ->get(route('agenda.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('agenda.slots.6.time', '11:00')
                ->where('agenda.slots.6.is_available', true)
                ->has('agenda.slots.6.appointments', 0));

        Carbon::setTestNow();
    }

    public function test_barbershop_agenda_blocks_only_slots_within_thirty_minutes(): void
    {
        Carbon::setTestNow('2026-07-07 14:20:00');

        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->get(route('agenda.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('agenda.slots.10.time', '13:00')
                ->where('agenda.slots.10.is_past', true)
                ->where('agenda.slots.10.is_available', false)
                ->where('agenda.slots.12.time', '14:00')
                ->where('agenda.slots.12.is_past', true)
                ->where('agenda.slots.12.is_available', false)
                ->where('agenda.slots.13.time', '14:30')
                ->where('agenda.slots.13.is_past', true)
                ->where('agenda.slots.13.is_available', false)
                ->where('agenda.slots.14.time', '15:00')
                ->where('agenda.slots.14.is_past', false)
                ->where('agenda.slots.14.is_available', true));

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => now()->setTime(15, 0)->seconds(0)->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'guest_name' => 'Cliente da Tarde',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        Carbon::setTestNow();
    }

    public function test_barbershop_owner_can_schedule_walk_in_appointment(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Carlos',
            'commission_percent' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $scheduledAt = now()->setTime(14, 0)->seconds(0);

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'barbershop_employee_id' => $employee->id,
                'guest_name' => 'Cliente Avulso',
                'guest_phone' => '67999998888',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->assertDatabaseHas('barbershop_appointments', [
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => null,
            'guest_name' => 'Cliente Avulso',
            'guest_phone' => '67999998888',
            'barbershop_employee_id' => $employee->id,
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        Carbon::setTestNow();
    }

    public function test_barbershop_owner_can_schedule_for_self_without_registered_client(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $scheduledAt = now()->setTime(15, 30)->seconds(0);

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Barba',
                'guest_name' => 'Walk-in',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->assertDatabaseHas('barbershop_appointments', [
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => null,
            'guest_name' => 'Walk-in',
            'barbershop_employee_id' => null,
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        Carbon::setTestNow();
    }

    public function test_client_can_view_appointments_page(): void
    {
        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $this->actingAs($client)
            ->get(route('client.appointments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/ClientIndex')
                ->where('barbershop.username', $barbershop->username));
    }

    public function test_booking_sends_email_to_barbershop_and_confirm_sends_email_to_client(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create(['email' => 'barbearia@example.com']);
        $client = User::factory()->customer()->create(['email' => 'cliente@example.com']);

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
            ->assertRedirect(route('client.appointments.index'))
            ->assertSessionHas('status', 'appointment-requested');

        Mail::assertSent(BarbershopAppointmentRequestedMail::class, function ($mail) use ($barbershop) {
            return $mail->hasTo($barbershop->email);
        });

        $appointment = BarbershopAppointment::query()->firstOrFail();

        $this->actingAs($barbershop)
            ->patch(route('agenda.update', $appointment), [
                'action' => 'confirm',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-updated');

        Mail::assertSent(ClientAppointmentConfirmedMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });

        Carbon::setTestNow();
    }

    public function test_rejecting_appointment_sends_email_to_client(): void
    {
        Mail::fake();

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create(['email' => 'cliente@example.com']);

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->addDay()->setTime(11, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_PENDING,
        ]);

        $this->actingAs($barbershop)
            ->patch(route('agenda.update', $appointment), [
                'action' => 'reject',
            ])
            ->assertRedirect();

        Mail::assertSent(ClientAppointmentRejectedMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    public function test_client_can_cancel_pending_appointment(): void
    {
        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->addDay()->setTime(14, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->patch(route('client.appointments.cancel', $appointment))
            ->assertRedirect(route('client.appointments.index'))
            ->assertSessionHas('status', 'appointment-cancelled');

        $this->assertDatabaseHas('barbershop_appointments', [
            'id' => $appointment->id,
            'status' => BarbershopAppointment::STATUS_CANCELLED,
        ]);
    }

    public function test_client_cannot_cancel_another_clients_appointment(): void
    {
        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();
        $otherClient = User::factory()->customer()->create();

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $otherClient->id,
            'scheduled_at' => now()->addDay()->setTime(14, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->patch(route('client.appointments.cancel', $appointment))
            ->assertForbidden();
    }

    public function test_owner_can_book_same_slot_for_different_employees(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();

        $employeeA = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Ana',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $employeeB = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Bruno',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $scheduledAt = now()->setTime(10, 0)->seconds(0);

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'barbershop_employee_id' => $employeeA->id,
                'guest_name' => 'Cliente A',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Barba',
                'package_type' => 'beard',
                'barbershop_employee_id' => $employeeB->id,
                'guest_name' => 'Cliente B',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->assertDatabaseHas('barbershop_appointments', [
            'barbershop_user_id' => $barbershop->id,
            'barbershop_employee_id' => $employeeA->id,
            'guest_name' => 'Cliente A',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        $this->assertDatabaseHas('barbershop_appointments', [
            'barbershop_user_id' => $barbershop->id,
            'barbershop_employee_id' => $employeeB->id,
            'guest_name' => 'Cliente B',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        Carbon::setTestNow();
    }

    public function test_owner_cannot_double_book_same_employee_on_slot(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Ana',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $scheduledAt = now()->setTime(10, 0)->seconds(0);

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'barbershop_employee_id' => $employee->id,
                'guest_name' => 'Cliente A',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->actingAs($barbershop)
            ->from(route('agenda.index', ['date' => '2026-07-07']))
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Barba',
                'package_type' => 'beard',
                'barbershop_employee_id' => $employee->id,
                'guest_name' => 'Cliente B',
            ])
            ->assertRedirect(route('agenda.index', ['date' => '2026-07-07']))
            ->assertSessionHasErrors('barbershop_employee_id');

        $this->assertSame(1, BarbershopAppointment::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('scheduled_at', $scheduledAt)
            ->count());

        Carbon::setTestNow();
    }

    public function test_owner_cannot_book_when_slot_capacity_is_full(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Ana',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $scheduledAt = now()->setTime(10, 0)->seconds(0);

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'guest_name' => 'Cliente Owner',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->actingAs($barbershop)
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Barba',
                'package_type' => 'beard',
                'barbershop_employee_id' => $employee->id,
                'guest_name' => 'Cliente Ana',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'appointment-created');

        $this->actingAs($barbershop)
            ->from(route('agenda.index', ['date' => '2026-07-07']))
            ->post(route('agenda.store'), [
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'service_label' => 'Corte Cabelo',
                'package_type' => 'cut',
                'guest_name' => 'Cliente Extra',
            ])
            ->assertRedirect(route('agenda.index', ['date' => '2026-07-07']))
            ->assertSessionHasErrors('scheduled_at');

        $this->assertSame(2, BarbershopAppointment::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('scheduled_at', $scheduledAt)
            ->count());

        Carbon::setTestNow();
    }

    public function test_agenda_payload_exposes_capacity_and_can_add_more(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Ana',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => null,
            'guest_name' => 'Walk-in',
            'scheduled_at' => now()->setTime(11, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
            'barbershop_employee_id' => $employee->id,
        ]);

        $this->actingAs($barbershop)
            ->get(route('agenda.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->where('agenda.slots.6.time', '11:00')
                ->where('agenda.slots.6.capacity', 2)
                ->where('agenda.slots.6.booked_count', 1)
                ->where('agenda.slots.6.can_add_more', true)
                ->where('agenda.slots.6.is_available', true)
                ->where('agenda.slots.6.available_assignee_ids', ['owner']));

        Carbon::setTestNow();
    }

    public function test_availability_endpoint_keeps_partial_slot_available_with_employees(): void
    {
        Carbon::setTestNow('2026-07-07 09:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();
        $tomorrow = now()->addDay()->toDateString();

        BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Ana',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($client)
            ->getJson(route('barbershop.appointments.availability', [
                'username' => $barbershop->username,
                'date' => $tomorrow,
            ]));

        $response->assertOk();

        $tenAm = collect($response->json('slots'))->firstWhere('time', '10:00');

        $this->assertTrue($tenAm['is_available']);

        Carbon::setTestNow();
    }
}
