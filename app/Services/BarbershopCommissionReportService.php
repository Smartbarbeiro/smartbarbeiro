<?php

namespace App\Services;

use App\Models\BarbershopAppointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BarbershopCommissionReportService
{
    public function __construct(
        private BarbershopServicePlanService $servicePlanService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function reportPayload(User $barbershop, ?string $month = null): array
    {
        $period = $this->resolveMonth($month);
        $appointments = $this->completedAppointmentsForPeriod($barbershop, $period['start'], $period['end']);

        $employees = $appointments
            ->groupBy(fn (BarbershopAppointment $appointment) => $appointment->barbershop_employee_id ?? 0)
            ->map(function (Collection $group, int|string $employeeId) {
                /** @var BarbershopAppointment $first */
                $first = $group->first();

                return [
                    'employee_id' => $employeeId === 0 ? null : (int) $employeeId,
                    'employee_name' => $first->employee?->name ?? 'Sem funcionário',
                    'commission_percent' => $first->employee
                        ? (float) $first->employee->commission_percent
                        : null,
                    'completed_services' => $group->count(),
                    'total_service_amount' => round((float) $group->sum('service_amount'), 2),
                    'total_commission_amount' => round((float) $group->sum('commission_amount'), 2),
                    'formatted_total_service_amount' => $this->formatMoney((float) $group->sum('service_amount')),
                    'formatted_total_commission_amount' => $this->formatMoney((float) $group->sum('commission_amount')),
                ];
            })
            ->sortBy('employee_name')
            ->values()
            ->all();

        $totalServiceAmount = round((float) $appointments->sum('service_amount'), 2);
        $totalCommissionAmount = round((float) $appointments->sum('commission_amount'), 2);

        return [
            'month' => $period['month'],
            'month_label' => $period['label'],
            'prev_month' => $period['prev_month'],
            'next_month' => $period['next_month'],
            'summary' => [
                'completed_services' => $appointments->count(),
                'total_service_amount' => $totalServiceAmount,
                'total_commission_amount' => $totalCommissionAmount,
                'formatted_total_service_amount' => $this->formatMoney($totalServiceAmount),
                'formatted_total_commission_amount' => $this->formatMoney($totalCommissionAmount),
            ],
            'employees' => $employees,
            'appointments' => $appointments
                ->map(fn (BarbershopAppointment $appointment) => $this->appointmentPayload($appointment))
                ->values()
                ->all(),
        ];
    }

    public function serviceAmountForAppointment(BarbershopAppointment $appointment): float
    {
        if ($appointment->package_type === null) {
            return 0.0;
        }

        $package = $appointment->barbershop
            ->servicePackages()
            ->where('type', $appointment->package_type)
            ->first();

        return $package ? (float) $package->monthly_price : 0.0;
    }

    public function commissionSnapshotForAppointment(BarbershopAppointment $appointment): array
    {
        $appointment->loadMissing(['employee', 'barbershop.servicePackages']);

        $serviceAmount = $this->serviceAmountForAppointment($appointment);
        $commissionPercent = $appointment->employee
            ? (float) $appointment->employee->commission_percent
            : 0.0;
        $commissionAmount = round($serviceAmount * ($commissionPercent / 100), 2);

        return [
            'service_amount' => $serviceAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
        ];
    }

    /**
     * @return array{month: string, label: string, start: Carbon, end: Carbon, prev_month: string, next_month: string}
     */
    private function resolveMonth(?string $month): array
    {
        $reference = filled($month)
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        return [
            'month' => $reference->format('Y-m'),
            'label' => $reference->locale('pt_BR')->translatedFormat('F \d\e Y'),
            'start' => $reference->copy()->startOfMonth(),
            'end' => $reference->copy()->endOfMonth(),
            'prev_month' => $reference->copy()->subMonth()->format('Y-m'),
            'next_month' => $reference->copy()->addMonth()->format('Y-m'),
        ];
    }

    /**
     * @return Collection<int, BarbershopAppointment>
     */
    private function completedAppointmentsForPeriod(User $barbershop, Carbon $start, Carbon $end): Collection
    {
        return BarbershopAppointment::query()
            ->with(['client:id,name', 'employee'])
            ->where('barbershop_user_id', $barbershop->id)
            ->where('status', BarbershopAppointment::STATUS_COMPLETED)
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderByDesc('scheduled_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function appointmentPayload(BarbershopAppointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'scheduled_date' => $appointment->scheduled_at->format('d/m/Y'),
            'scheduled_time' => $appointment->scheduled_at->format('H:i'),
            'service_label' => $appointment->service_label,
            'client_name' => $appointment->displayName(),
            'employee_name' => $appointment->performerName() ?? 'Proprietário',
            'service_amount' => (float) $appointment->service_amount,
            'commission_percent' => (float) $appointment->commission_percent,
            'commission_amount' => (float) $appointment->commission_amount,
            'formatted_service_amount' => $this->formatMoney((float) $appointment->service_amount),
            'formatted_commission_amount' => $this->formatMoney((float) $appointment->commission_amount),
            'formatted_commission_percent' => number_format((float) $appointment->commission_percent, 2, ',', '.').'%',
        ];
    }

    private function formatMoney(float $amount): string
    {
        return 'R$ '.number_format($amount, 2, ',', '.');
    }
}
