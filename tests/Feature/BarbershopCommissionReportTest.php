<?php

namespace Tests\Feature;

use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Models\BarbershopServicePackage;
use App\Models\User;
use App\Services\BarbershopAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BarbershopCommissionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_appointment_snapshots_commission_amounts(): void
    {
        $barbershop = User::factory()->create();

        $barbershop->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 80]);

        $client = User::factory()->customer()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Pedro',
            'commission_percent' => 50,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $appointment = BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'barbershop_employee_id' => $employee->id,
            'scheduled_at' => now()->setTime(10, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'package_type' => BarbershopServicePackage::TYPE_CUT,
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
        ]);

        app(BarbershopAppointmentService::class)->complete($appointment);

        $this->assertDatabaseHas('barbershop_appointments', [
            'id' => $appointment->id,
            'status' => BarbershopAppointment::STATUS_COMPLETED,
            'service_amount' => 80,
            'commission_percent' => 50,
            'commission_amount' => 40,
        ]);
    }

    public function test_barbershop_can_view_commission_report_for_month(): void
    {
        Carbon::setTestNow('2026-07-15 10:00:00');

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        $employee = BarbershopEmployee::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'name' => 'Pedro',
            'commission_percent' => 40,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'barbershop_employee_id' => $employee->id,
            'scheduled_at' => now()->setTime(11, 0),
            'duration_minutes' => 30,
            'service_label' => 'Corte Cabelo',
            'package_type' => BarbershopServicePackage::TYPE_CUT,
            'status' => BarbershopAppointment::STATUS_COMPLETED,
            'service_amount' => 100,
            'commission_percent' => 40,
            'commission_amount' => 40,
        ]);

        $this->actingAs($barbershop)
            ->get(route('commissions.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Commissions/Index')
                ->where('report.summary.completed_services', 1)
                ->where('report.summary.total_commission_amount', 40)
                ->where('report.employees.0.employee_name', 'Pedro'));

        Carbon::setTestNow();
    }
}
