<?php

namespace App\Services;

use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Models\BarbershopServicePackage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BarbershopAppointmentService
{
    public const OPEN_HOUR = 8;

    public const CLOSE_HOUR = 20;

    public const SLOT_MINUTES = 30;

    /**
     * @return array<string, mixed>
     */
    public function agendaPayload(User $barbershop, ?string $date = null): array
    {
        $selectedDate = $this->resolveDate($date)->startOfDay();
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);

        $appointments = $this->appointmentsForDate($barbershop, $selectedDate);

        return [
            'date' => $selectedDate->toDateString(),
            'formatted_date' => $selectedDate->locale('pt_BR')->translatedFormat('l, j \d\e F \d\e Y'),
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            'prev_week_date' => $selectedDate->copy()->subWeek()->toDateString(),
            'next_week_date' => $selectedDate->copy()->addWeek()->toDateString(),
            'prev_day_date' => $selectedDate->copy()->subDay()->toDateString(),
            'next_day_date' => $selectedDate->copy()->addDay()->toDateString(),
            'week_days' => $this->weekDaysPayload($barbershop, $weekStart, $selectedDate),
            'slots' => $this->slotsPayload($barbershop, $selectedDate, $appointments),
            'pending_appointments' => $appointments
                ->filter(fn (BarbershopAppointment $appointment) => $appointment->isPending())
                ->map(fn (BarbershopAppointment $appointment) => $appointment->toPayload())
                ->values()
                ->all(),
            'employees' => app(BarbershopEmployeeService::class)->payloadFor($barbershop),
        ];
    }

    /**
     * @return list<array{time: string, label: string, is_available: bool}>
     */
    public function availableSlotsForDate(User $barbershop, Carbon $date): array
    {
        $appointments = $this->appointmentsForDate($barbershop, $date);
        $takenTimes = $appointments
            ->filter(fn (BarbershopAppointment $appointment) => $appointment->isActive())
            ->map(fn (BarbershopAppointment $appointment) => $appointment->scheduled_at->format('H:i'))
            ->all();

        return collect($this->slotTimes())
            ->map(function (string $time) use ($date, $takenTimes) {
                $slotMoment = $date->copy()->setTimeFromTimeString($time.':00');
                $isPast = $slotMoment->isPast();
                $isTaken = in_array($time, $takenTimes, true);

                return [
                    'time' => $time,
                    'label' => $time,
                    'is_available' => ! $isPast && ! $isTaken,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{scheduled_at: string, service_label: string, package_type?: string|null, client_notes?: string|null}  $data
     */
    public function createBooking(User $barbershop, User $client, array $data): BarbershopAppointment
    {
        $scheduledAt = Carbon::parse($data['scheduled_at'])->seconds(0);

        $this->assertSlotIsBookable($barbershop, $scheduledAt);

        return BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $client->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => self::SLOT_MINUTES,
            'service_label' => trim($data['service_label']),
            'package_type' => $data['package_type'] ?? null,
            'status' => BarbershopAppointment::STATUS_PENDING,
            'client_notes' => filled($data['client_notes'] ?? null)
                ? trim((string) $data['client_notes'])
                : null,
        ]);
    }

    public function confirm(
        BarbershopAppointment $appointment,
        ?BarbershopEmployee $employee = null,
    ): BarbershopAppointment {
        if ($employee !== null) {
            abort_unless($employee->barbershop_user_id === $appointment->barbershop_user_id, 422);
            abort_unless($employee->is_active, 422);
        }

        $appointment->update([
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
            'barbershop_employee_id' => $employee?->id,
        ]);

        return $appointment->fresh(['client', 'employee']);
    }

    public function assignEmployee(
        BarbershopAppointment $appointment,
        ?BarbershopEmployee $employee,
    ): BarbershopAppointment {
        if ($employee !== null) {
            abort_unless($employee->barbershop_user_id === $appointment->barbershop_user_id, 422);
            abort_unless($employee->is_active, 422);
        }

        $appointment->update([
            'barbershop_employee_id' => $employee?->id,
        ]);

        return $appointment->fresh(['client', 'employee']);
    }

    public function reject(BarbershopAppointment $appointment): BarbershopAppointment
    {
        $appointment->update([
            'status' => BarbershopAppointment::STATUS_REJECTED,
        ]);

        return $appointment->fresh(['client', 'employee']);
    }

    public function cancel(BarbershopAppointment $appointment): BarbershopAppointment
    {
        $appointment->update([
            'status' => BarbershopAppointment::STATUS_CANCELLED,
        ]);

        return $appointment->fresh(['client', 'employee']);
    }

    public function complete(BarbershopAppointment $appointment): BarbershopAppointment
    {
        $appointment->update([
            'status' => BarbershopAppointment::STATUS_COMPLETED,
        ]);

        return $appointment->fresh(['client', 'employee']);
    }

    public function serviceLabelForPackageType(?string $packageType): string
    {
        return match ($packageType) {
            BarbershopServicePackage::TYPE_CUT => 'Corte Cabelo',
            BarbershopServicePackage::TYPE_CUT_BEARD => 'Corte Cabelo + Barba',
            default => 'Serviço',
        };
    }

    private function resolveDate(?string $date): Carbon
    {
        if (blank($date)) {
            return now()->startOfDay();
        }

        return Carbon::parse($date)->startOfDay();
    }

    /**
     * @return Collection<int, BarbershopAppointment>
     */
    private function appointmentsForDate(User $barbershop, Carbon $date): Collection
    {
        return BarbershopAppointment::query()
            ->with(['client:id,name', 'employee'])
            ->where('barbershop_user_id', $barbershop->id)
            ->whereBetween('scheduled_at', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function slotTimes(): array
    {
        $slots = [];

        for ($hour = self::OPEN_HOUR; $hour < self::CLOSE_HOUR; $hour++) {
            foreach ([0, 30] as $minute) {
                $slots[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return $slots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function slotsPayload(User $barbershop, Carbon $date, Collection $appointments): array
    {
        $appointmentsByTime = $appointments->groupBy(
            fn (BarbershopAppointment $appointment) => $appointment->scheduled_at->format('H:i'),
        );

        return collect($this->slotTimes())
            ->map(function (string $time) use ($date, $appointmentsByTime, $barbershop) {
                $slotAppointments = ($appointmentsByTime[$time] ?? collect())
                    ->map(fn (BarbershopAppointment $appointment) => $appointment->toPayload())
                    ->values()
                    ->all();

                $slotMoment = $date->copy()->setTimeFromTimeString($time.':00');

                return [
                    'time' => $time,
                    'label' => $time,
                    'is_past' => $slotMoment->isPast(),
                    'is_available' => $slotAppointments === [] && ! $slotMoment->isPast(),
                    'appointments' => $slotAppointments,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function weekDaysPayload(User $barbershop, Carbon $weekStart, Carbon $selectedDate): array
    {
        return collect(range(0, 6))
            ->map(function (int $offset) use ($weekStart, $selectedDate, $barbershop) {
                $day = $weekStart->copy()->addDays($offset);
                $count = BarbershopAppointment::query()
                    ->where('barbershop_user_id', $barbershop->id)
                    ->whereBetween('scheduled_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                    ->whereIn('status', [
                        BarbershopAppointment::STATUS_PENDING,
                        BarbershopAppointment::STATUS_CONFIRMED,
                    ])
                    ->count();

                return [
                    'date' => $day->toDateString(),
                    'day' => $day->day,
                    'weekday_label' => mb_strtoupper($day->locale('pt_BR')->translatedFormat('ddd')),
                    'is_selected' => $day->isSameDay($selectedDate),
                    'is_today' => $day->isToday(),
                    'appointment_count' => $count,
                ];
            })
            ->values()
            ->all();
    }

    private function assertSlotIsBookable(User $barbershop, Carbon $scheduledAt): void
    {
        if ($scheduledAt->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Escolha um horário no futuro.',
            ]);
        }

        if (! in_array($scheduledAt->format('i'), ['00', '30'], true)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Os horários devem ser de 30 em 30 minutos.',
            ]);
        }

        $hour = (int) $scheduledAt->format('G');
        $minute = (int) $scheduledAt->format('i');
        $slotStart = $hour * 60 + $minute;
        $open = self::OPEN_HOUR * 60;
        $close = self::CLOSE_HOUR * 60;

        if ($slotStart < $open || $slotStart >= $close) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Escolha um horário dentro do expediente da barbearia.',
            ]);
        }

        $exists = BarbershopAppointment::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', [
                BarbershopAppointment::STATUS_PENDING,
                BarbershopAppointment::STATUS_CONFIRMED,
            ])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Este horário já está reservado.',
            ]);
        }
    }
}
