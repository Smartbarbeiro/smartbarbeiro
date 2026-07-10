<?php

namespace App\Services;

use App\Models\BarbershopAppointment;
use App\Models\BarbershopEmployee;
use App\Models\BarbershopServicePackage;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BarbershopAppointmentService
{
    public const OPEN_HOUR = 8;

    public const CLOSE_HOUR = 20;

    public const SLOT_MINUTES = 30;

    /** @var list<string> */
    private const WEEKDAY_LABELS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    public function __construct(
        private BarbershopCommissionReportService $commissionReportService,
        private BarbershopServicePlanService $servicePlanService,
    ) {}

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
            'pending_appointments' => $this->pendingAppointmentsPayload($barbershop),
            'pending_appointments_count' => $this->pendingAppointmentsCount($barbershop),
            'employees' => app(BarbershopEmployeeService::class)->payloadFor($barbershop),
            'owner' => [
                'id' => $barbershop->id,
                'name' => $barbershop->name,
            ],
            'services' => $this->servicesForAgenda($barbershop),
            'clients' => app(BarbershopClientAudienceService::class)
                ->clientsFor($barbershop)
                ->map(fn (User $client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array{
     *     scheduled_at: string,
     *     service_label: string,
     *     package_type?: string|null,
     *     barbershop_employee_id?: int|null,
     *     client_user_id?: int|null,
     *     guest_name?: string|null,
     *     guest_phone?: string|null,
     *     client_notes?: string|null
     * }  $data
     */
    public function createOwnerBooking(User $barbershop, array $data): BarbershopAppointment
    {
        $scheduledAt = Carbon::parse($data['scheduled_at'])->seconds(0);

        $this->assertSlotIsBookable($barbershop, $scheduledAt, allowCurrentHour: true);

        $employeeId = $data['barbershop_employee_id'] ?? null;

        if ($employeeId !== null) {
            $employee = BarbershopEmployee::query()->findOrFail($employeeId);
            abort_unless($employee->barbershop_user_id === $barbershop->id, 422);
            abort_unless($employee->is_active, 422);
        }

        return BarbershopAppointment::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'client_user_id' => $data['client_user_id'] ?? null,
            'guest_name' => filled($data['guest_name'] ?? null)
                ? trim((string) $data['guest_name'])
                : null,
            'guest_phone' => filled($data['guest_phone'] ?? null)
                ? trim((string) $data['guest_phone'])
                : null,
            'barbershop_employee_id' => $employeeId,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => self::SLOT_MINUTES,
            'service_label' => trim($data['service_label']),
            'package_type' => $data['package_type'] ?? null,
            'status' => BarbershopAppointment::STATUS_CONFIRMED,
            'client_notes' => filled($data['client_notes'] ?? null)
                ? trim((string) $data['client_notes'])
                : null,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function servicesForAgenda(User $barbershop): array
    {
        $packages = collect($this->servicePlanService->packagesPayload($barbershop))
            ->map(fn (array $package) => [
                'key' => 'package:'.$package['type'],
                'label' => $package['label'],
                'package_type' => $package['type'],
            ]);

        $addons = collect($this->servicePlanService->addonsPayload($barbershop))
            ->map(fn (array $addon) => [
                'key' => 'addon:'.$addon['id'],
                'label' => $addon['name'],
                'package_type' => null,
            ]);

        return $packages
            ->merge($addons)
            ->when(
                $packages->isEmpty() && $addons->isEmpty(),
                fn ($collection) => $collection->push([
                    'key' => 'service:generic',
                    'label' => 'Serviço',
                    'package_type' => null,
                ]),
            )
            ->values()
            ->all();
    }

    /**
     * @return list<array{time: string, label: string, is_available: bool}>
     */
    public function availableSlotsForDate(User $barbershop, Carbon $date): array
    {
        if ($date->lt($this->minBookingDate())) {
            return [];
        }

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
     * @return list<array<string, mixed>>
     */
    public function availableBookingDays(User $barbershop, int $lookaheadDays = 28): array
    {
        $days = [];

        for ($offset = 0; $offset < $lookaheadDays; $offset++) {
            $date = $this->minBookingDate()->copy()->addDays($offset);
            $availableCount = collect($this->availableSlotsForDate($barbershop, $date))
                ->where('is_available', true)
                ->count();

            if ($availableCount === 0) {
                continue;
            }

            $days[] = [
                'date' => $date->toDateString(),
                'label' => $date->locale('pt_BR')->translatedFormat('D, d/m'),
                'weekday_label' => self::WEEKDAY_LABELS[$date->dayOfWeek],
                'day' => $date->day,
                'month_label' => $date->locale('pt_BR')->translatedFormat('M'),
                'available_slots_count' => $availableCount,
            ];
        }

        return $days;
    }

    /**
     * @return array{days: list<array<string, mixed>>, date: ?string, slots: list<array{time: string, label: string, is_available: bool}>}
     */
    public function bookingAvailabilityPayload(User $barbershop, ?string $requestedDate = null): array
    {
        $minDate = $this->minBookingDate();
        $maxDate = $minDate->copy()->addDays(60);

        $selectedDate = filled($requestedDate)
            ? Carbon::parse($requestedDate)->startOfDay()
            : $minDate->copy();

        if ($selectedDate->lt($minDate)) {
            $selectedDate = $minDate->copy();
        }

        if ($selectedDate->gt($maxDate)) {
            $selectedDate = $maxDate->copy();
        }

        return [
            'date' => $selectedDate->toDateString(),
            'min_date' => $minDate->toDateString(),
            'max_date' => $maxDate->toDateString(),
            'slots' => $this->availableSlotsForDate($barbershop, $selectedDate),
        ];
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
        $appointment->loadMissing(['employee', 'barbershop']);

        $appointment->update([
            'status' => BarbershopAppointment::STATUS_COMPLETED,
            ...$this->commissionReportService->commissionSnapshotForAppointment($appointment),
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

    /**
     * @return list<array<string, mixed>>
     */
    public function clientAppointmentsPayload(User $client, User $barbershop): array
    {
        return BarbershopAppointment::query()
            ->with(['employee', 'barbershop:id,name,username'])
            ->where('barbershop_user_id', $barbershop->id)
            ->where('client_user_id', $client->id)
            ->orderByDesc('scheduled_at')
            ->limit(30)
            ->get()
            ->map(function (BarbershopAppointment $appointment) use ($barbershop) {
                return [
                    ...$appointment->toPayload(),
                    'formatted_scheduled_at' => $appointment->scheduled_at
                        ->timezone(config('app.timezone'))
                        ->locale('pt_BR')
                        ->translatedFormat('d/m/Y \à\s H:i'),
                    'barbershop' => [
                        'name' => $barbershop->name,
                        'username' => $barbershop->username,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function pendingAppointmentsCount(User $barbershop): int
    {
        return BarbershopAppointment::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('status', BarbershopAppointment::STATUS_PENDING)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * @return array{
     *     username: string,
     *     barbershopName: string,
     *     defaultServiceLabel: string,
     *     defaultPackageType: string|null
     * }|null
     */
    public function clientBookingPayload(User $client): ?array
    {
        if ($client->isBarbershop() || $client->isAdmin()) {
            return null;
        }

        $barbershop = $client->primaryBarbershop();

        if ($barbershop === null) {
            return null;
        }

        if (! app(BarbershopClientAccessService::class)->hasSignedUp($barbershop, $client)) {
            return null;
        }

        $activeServicePlanSubscription = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $client->id)
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->latest()
            ->first();

        $packageType = $activeServicePlanSubscription?->isActive()
            ? $activeServicePlanSubscription->package_type
            : null;

        if ($packageType !== null) {
            return [
                'username' => $barbershop->username,
                'barbershopName' => $barbershop->name,
                'defaultServiceLabel' => $this->serviceLabelForPackageType($packageType),
                'defaultPackageType' => $packageType,
            ];
        }

        $enabledPackage = collect(
            app(BarbershopServicePlanService::class)->publicPlansPayload($barbershop)['packages'] ?? [],
        )->first(fn (array $package) => $package['is_enabled'] ?? false);

        return [
            'username' => $barbershop->username,
            'barbershopName' => $barbershop->name,
            'defaultServiceLabel' => $enabledPackage['label'] ?? 'Serviço',
            'defaultPackageType' => $enabledPackage['type'] ?? null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pendingAppointmentsPayload(User $barbershop): array
    {
        return BarbershopAppointment::query()
            ->with(['client:id,name', 'employee'])
            ->where('barbershop_user_id', $barbershop->id)
            ->where('status', BarbershopAppointment::STATUS_PENDING)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->get()
            ->map(function (BarbershopAppointment $appointment) {
                return [
                    ...$appointment->toPayload(),
                    'formatted_scheduled_at' => $appointment->scheduled_at
                        ->timezone(config('app.timezone'))
                        ->locale('pt_BR')
                        ->translatedFormat('d/m/Y \à\s H:i'),
                ];
            })
            ->values()
            ->all();
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
                $timeAppointments = $appointmentsByTime[$time] ?? collect();

                $slotAppointments = $timeAppointments
                    ->filter(fn (BarbershopAppointment $appointment) => $appointment->isVisibleOnAgenda())
                    ->map(fn (BarbershopAppointment $appointment) => $appointment->toPayload())
                    ->values()
                    ->all();

                $slotMoment = $date->copy()->setTimeFromTimeString($time.':00');
                $hasActiveBooking = $timeAppointments
                    ->contains(fn (BarbershopAppointment $appointment) => $appointment->isActive());
                $isPast = $this->isSlotHourPast($slotMoment);

                return [
                    'time' => $time,
                    'label' => $time,
                    'is_past' => $isPast,
                    'is_available' => ! $hasActiveBooking && ! $isPast,
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
                    'weekday_label' => self::WEEKDAY_LABELS[$day->dayOfWeek],
                    'is_selected' => $day->isSameDay($selectedDate),
                    'is_today' => $day->isToday(),
                    'appointment_count' => $count,
                ];
            })
            ->values()
            ->all();
    }

    private function assertSlotIsBookable(User $barbershop, Carbon $scheduledAt, bool $allowCurrentHour = false): void
    {
        if ($scheduledAt->copy()->startOfDay()->lt($this->minBookingDate())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Escolha uma data a partir de hoje.',
            ]);
        }

        $isPast = $allowCurrentHour
            ? $this->isSlotHourPast($scheduledAt)
            : $scheduledAt->isPast();

        if ($isPast) {
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

    /**
     * For barbershop agenda/owner booking: a same-day slot stays free until its hour ends.
     * Example: at 14:20, both 14:00 and 14:30 remain available.
     */
    private function isSlotHourPast(Carbon $slotMoment): bool
    {
        return now()->gte($slotMoment->copy()->startOfHour()->addHour());
    }

    private function minBookingDate(): Carbon
    {
        return now()->startOfDay();
    }

    /**
     * @param  list<array<string, mixed>>  $days
     */
    private function resolveBookingDate(?string $requestedDate, array $days): ?Carbon
    {
        if ($days === []) {
            return null;
        }

        if (filled($requestedDate)) {
            $requested = Carbon::parse($requestedDate)->startOfDay();

            foreach ($days as $day) {
                if ($day['date'] === $requested->toDateString()) {
                    return $requested;
                }
            }
        }

        return Carbon::parse($days[0]['date'])->startOfDay();
    }
}
