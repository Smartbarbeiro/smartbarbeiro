<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    username: {
        type: String,
        required: true,
    },
    defaultServiceLabel: {
        type: String,
        required: true,
    },
    defaultPackageType: {
        type: String,
        default: null,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const availableSlots = ref(buildDefaultSlots());
const selectedDate = ref('');
const selectedTime = ref('');
const minDate = ref('');
const maxDate = ref('');
const loadingSlots = ref(false);
const availabilityError = ref(null);
const morningExpanded = ref(false);
const afternoonExpanded = ref(false);
const viewDate = ref(new Date());

const MORNING_CUTOFF_HOUR = 12;
const MONTHS_AHEAD = 2;
const weekdayShortLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

const form = useForm({
    scheduled_at: '',
    service_label: props.defaultServiceLabel,
    package_type: props.defaultPackageType,
    client_notes: '',
});

function buildDefaultSlots() {
    const slots = [];

    for (let hour = 8; hour < 20; hour += 1) {
        for (const minute of [0, 30]) {
            const time = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;

            slots.push({
                time,
                label: time,
                is_available: false,
            });
        }
    }

    return slots;
}

function formatDateInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const MONTH_LABELS = [
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro',
];

const WEEKDAY_LABELS = [
    'Domingo',
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
];

const todayDate = () => formatDateInput(new Date());

function parseLocalDate(dateStr) {
    if (!dateStr) {
        return null;
    }

    const [year, month, day] = dateStr.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day);
}

function formatBookingDateLabel(dateStr) {
    const date = parseLocalDate(dateStr);

    if (!date) {
        return 'Escolha o dia';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = MONTH_LABELS[date.getMonth()];
    const weekday = WEEKDAY_LABELS[date.getDay()];
    const label = `${day} de ${month} - ${weekday}`;

    if (dateStr === todayDate()) {
        return `${label} (Hoje)`;
    }

    return label;
}

const selectedDateLabel = computed(() =>
    formatBookingDateLabel(selectedDate.value),
);

const currentMonthStart = computed(() => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), 1);
});

const maxMonthStart = computed(() => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth() + MONTHS_AHEAD, 1);
});

const maxBookingDate = computed(() => {
    const now = new Date();

    return formatDateInput(
        new Date(now.getFullYear(), now.getMonth() + MONTHS_AHEAD + 1, 0),
    );
});

const canGoToPreviousMonth = computed(() => {
    const viewed = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth(),
        1,
    );

    return viewed > currentMonthStart.value;
});

const canGoToNextMonth = computed(() => {
    const viewed = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth(),
        1,
    );

    return viewed < maxMonthStart.value;
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric',
    }),
);

const calendarDates = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const startWeekday = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = todayDate();
    const latest = maxDate.value || maxBookingDate.value;
    const cells = [];

    for (let index = 0; index < startWeekday; index += 1) {
        cells.push({
            type: 'empty',
            key: `empty-${year}-${month}-${index}`,
        });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = formatDateInput(new Date(year, month, day));
        const isDisabled = date < today || (latest && date > latest);

        cells.push({
            type: 'current',
            day,
            date,
            key: `day-${date}`,
            isToday: date === today,
            isSelected: selectedDate.value === date,
            isDisabled,
        });
    }

    return cells;
});

const goToPreviousMonth = () => {
    if (!canGoToPreviousMonth.value) {
        return;
    }

    viewDate.value = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth() - 1,
        1,
    );
};

const goToNextMonth = () => {
    if (!canGoToNextMonth.value) {
        return;
    }

    viewDate.value = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth() + 1,
        1,
    );
};

const selectCalendarDate = (date, isDisabled) => {
    if (isDisabled || loadingSlots.value || selectedDate.value === date) {
        return;
    }

    selectedDate.value = date;
    selectedTime.value = '';
    syncScheduledAt();
    loadSlotsForDate(date);
};

function mergeSlots(apiSlots) {
    const defaults = buildDefaultSlots();

    if (!apiSlots?.length) {
        return defaults;
    }

    const slotsByTime = Object.fromEntries(
        apiSlots.map((slot) => [slot.time, slot]),
    );

    return defaults.map((slot) => slotsByTime[slot.time] ?? slot);
}

const hasAvailableSlots = computed(() =>
    availableSlots.value.some((slot) => slot.is_available),
);

const morningSlots = computed(() =>
    availableSlots.value.filter(
        (slot) => Number(slot.time.split(':')[0]) < MORNING_CUTOFF_HOUR,
    ),
);

const afternoonSlots = computed(() =>
    availableSlots.value.filter(
        (slot) => Number(slot.time.split(':')[0]) >= MORNING_CUTOFF_HOUR,
    ),
);

const availableCount = (slots) =>
    slots.filter((slot) => slot.is_available).length;

const syncAccordionSections = () => {
    const morningAvailable = availableCount(morningSlots.value);
    const afternoonAvailable = availableCount(afternoonSlots.value);

    morningExpanded.value = false;
    afternoonExpanded.value = false;

    if (morningAvailable > 0) {
        morningExpanded.value = true;

        return;
    }

    if (afternoonAvailable > 0) {
        afternoonExpanded.value = true;
    }
};

const syncScheduledAt = () => {
    if (selectedDate.value && selectedTime.value) {
        form.scheduled_at = `${selectedDate.value}T${selectedTime.value}:00`;
        return;
    }

    form.scheduled_at = '';
};

const loadSlotsForDate = async (date) => {
    if (!date) {
        availableSlots.value = buildDefaultSlots();
        return;
    }

    loadingSlots.value = true;
    availabilityError.value = null;
    availableSlots.value = buildDefaultSlots();

    try {
        const url = new URL(
            route('barbershop.appointments.availability', {
                username: props.username,
            }),
            window.location.origin,
        );
        url.searchParams.set('date', date);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('availability request failed');
        }

        const data = await response.json();

        availableSlots.value = mergeSlots(data.slots ?? []);
        minDate.value = data.min_date ?? todayDate();
        maxDate.value = data.max_date
            ? data.max_date < maxBookingDate.value
                ? data.max_date
                : maxBookingDate.value
            : maxBookingDate.value;
        selectedDate.value = data.date ?? date;
        selectedTime.value = '';

        const selected = parseLocalDate(selectedDate.value);

        if (selected) {
            viewDate.value = new Date(
                selected.getFullYear(),
                selected.getMonth(),
                1,
            );
        }

        syncScheduledAt();
    } catch {
        availableSlots.value = buildDefaultSlots();
        availabilityError.value =
            'Não foi possível atualizar a disponibilidade. Os horários padrão estão exibidos.';
    } finally {
        loadingSlots.value = false;
        syncAccordionSections();
    }
};

const resetPicker = () => {
    const today = todayDate();
    const now = new Date();

    minDate.value = today;
    maxDate.value = maxBookingDate.value;
    selectedDate.value = today;
    selectedTime.value = '';
    viewDate.value = new Date(now.getFullYear(), now.getMonth(), 1);
    availableSlots.value = buildDefaultSlots();
    availabilityError.value = null;
    morningExpanded.value = false;
    afternoonExpanded.value = false;
    syncScheduledAt();
};

watch(
    () => props.show,
    (open) => {
        if (open) {
            form.service_label = props.defaultServiceLabel;
            form.package_type = props.defaultPackageType;
            form.clearErrors();
            resetPicker();
            loadSlotsForDate(todayDate());
        }
    },
);

watch(selectedTime, () => {
    syncScheduledAt();
});

const pickTime = (time) => {
    const slot = availableSlots.value.find((item) => item.time === time);

    if (!slot?.is_available) {
        return;
    }

    selectedTime.value = time;

    const hour = Number(time.split(':')[0]);

    if (hour < MORNING_CUTOFF_HOUR) {
        morningExpanded.value = true;
    } else {
        afternoonExpanded.value = true;
    }
};

const close = () => {
    emit('close');
};

const submit = () => {
    syncScheduledAt();

    form.post(
        route('barbershop.appointments.store', { username: props.username }),
        {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('client_notes');
                form.scheduled_at = '';
                selectedTime.value = '';
                form.service_label = props.defaultServiceLabel;
                form.package_type = props.defaultPackageType;
                close();
            },
        },
    );
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="book-appointment-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="book-appointment-title"
        >
            <div class="book-appointment-modal__backdrop" @click="close" />

            <div class="book-appointment-modal__panel">
                <div
                    class="d-flex justify-content-between align-items-start gap-3 mb-3"
                >
                    <div>
                        <h2 id="book-appointment-title" class="h5 mb-1">
                            Agendar horário
                        </h2>
                        <p class="small text-secondary mb-0">
                            Escolha o dia e depois toque em um horário livre.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Fechar"
                        @click="close"
                    />
                </div>

                <form class="d-flex flex-column gap-3" @submit.prevent="submit">
                    <div class="book-appointment-modal__field">
                        <label class="form-label">Dia</label>
                        <div class="preferred-haircut-calendar book-appointment-calendar">
                            <div class="preferred-haircut-calendar__header">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    :disabled="!canGoToPreviousMonth"
                                    aria-label="Mês anterior"
                                    @click="goToPreviousMonth"
                                >
                                    ‹
                                </button>
                                <p class="preferred-haircut-calendar__month mb-0">
                                    {{ monthLabel }}
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    :disabled="!canGoToNextMonth"
                                    aria-label="Próximo mês"
                                    @click="goToNextMonth"
                                >
                                    ›
                                </button>
                            </div>

                            <ul class="preferred-haircut-calendar__days">
                                <li
                                    v-for="label in weekdayShortLabels"
                                    :key="label"
                                >
                                    {{ label }}
                                </li>
                            </ul>

                            <ul class="preferred-haircut-calendar__dates">
                                <li
                                    v-for="cell in calendarDates"
                                    :key="cell.key"
                                    :class="{
                                        empty: cell.type === 'empty',
                                        today: cell.isToday,
                                        selected: cell.isSelected,
                                        disabled: cell.isDisabled,
                                    }"
                                >
                                    <button
                                        v-if="cell.type === 'current'"
                                        type="button"
                                        class="preferred-haircut-calendar__date-btn"
                                        :aria-label="
                                            formatBookingDateLabel(cell.date)
                                        "
                                        :aria-pressed="cell.isSelected"
                                        :disabled="
                                            cell.isDisabled || loadingSlots
                                        "
                                        @click="
                                            selectCalendarDate(
                                                cell.date,
                                                cell.isDisabled,
                                            )
                                        "
                                    >
                                        {{ cell.day }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <p
                            class="preferred-haircut-calendar__selection small mb-0 mt-2"
                        >
                            {{ selectedDateLabel }}
                        </p>
                    </div>

                    <div class="book-appointment-modal__field">
                        <label class="form-label">Horário</label>
                        <p
                            v-if="loadingSlots"
                            class="book-appointment-modal__hint small mb-2"
                        >
                            Atualizando horários livres...
                        </p>
                        <div
                            class="book-appointment-time-sections"
                            role="listbox"
                            aria-label="Horários disponíveis"
                        >
                            <section class="book-appointment-time-section">
                                <button
                                    type="button"
                                    class="book-appointment-time-section__toggle"
                                    :aria-expanded="morningExpanded"
                                    :disabled="loadingSlots"
                                    @click="
                                        morningExpanded = !morningExpanded
                                    "
                                >
                                    <span class="book-appointment-time-section__label">
                                        <span>Manhã</span>
                                        <span
                                            class="book-appointment-time-section__range"
                                        >
                                            08:00 – 11:30
                                            <template
                                                v-if="
                                                    !loadingSlots &&
                                                    availableCount(
                                                        morningSlots,
                                                    ) > 0
                                                "
                                            >
                                                ·
                                                {{
                                                    availableCount(morningSlots)
                                                }}
                                                livre{{
                                                    availableCount(
                                                        morningSlots,
                                                    ) === 1
                                                        ? ''
                                                        : 's'
                                                }}
                                            </template>
                                        </span>
                                    </span>
                                    <i
                                        class="bi book-appointment-time-section__chevron"
                                        :class="
                                            morningExpanded
                                                ? 'bi-chevron-up'
                                                : 'bi-chevron-down'
                                        "
                                        aria-hidden="true"
                                    />
                                </button>

                                <div
                                    v-show="morningExpanded"
                                    class="book-appointment-time-grid"
                                >
                                    <button
                                        v-for="slot in morningSlots"
                                        :key="slot.time"
                                        type="button"
                                        class="book-appointment-time-grid__item"
                                        :class="{
                                            'book-appointment-time-grid__item--selected':
                                                selectedTime === slot.time,
                                            'book-appointment-time-grid__item--available':
                                                slot.is_available,
                                            'book-appointment-time-grid__item--busy':
                                                !slot.is_available,
                                        }"
                                        :disabled="
                                            !slot.is_available || loadingSlots
                                        "
                                        :aria-selected="
                                            selectedTime === slot.time
                                        "
                                        @click="pickTime(slot.time)"
                                    >
                                        {{ slot.label }}
                                    </button>
                                </div>
                            </section>

                            <section class="book-appointment-time-section">
                                <button
                                    type="button"
                                    class="book-appointment-time-section__toggle"
                                    :aria-expanded="afternoonExpanded"
                                    :disabled="loadingSlots"
                                    @click="
                                        afternoonExpanded = !afternoonExpanded
                                    "
                                >
                                    <span class="book-appointment-time-section__label">
                                        <span>Tarde</span>
                                        <span
                                            class="book-appointment-time-section__range"
                                        >
                                            12:00 – 19:30
                                            <template
                                                v-if="
                                                    !loadingSlots &&
                                                    availableCount(
                                                        afternoonSlots,
                                                    ) > 0
                                                "
                                            >
                                                ·
                                                {{
                                                    availableCount(
                                                        afternoonSlots,
                                                    )
                                                }}
                                                livre{{
                                                    availableCount(
                                                        afternoonSlots,
                                                    ) === 1
                                                        ? ''
                                                        : 's'
                                                }}
                                            </template>
                                        </span>
                                    </span>
                                    <i
                                        class="bi book-appointment-time-section__chevron"
                                        :class="
                                            afternoonExpanded
                                                ? 'bi-chevron-up'
                                                : 'bi-chevron-down'
                                        "
                                        aria-hidden="true"
                                    />
                                </button>

                                <div
                                    v-show="afternoonExpanded"
                                    class="book-appointment-time-grid"
                                >
                                    <button
                                        v-for="slot in afternoonSlots"
                                        :key="slot.time"
                                        type="button"
                                        class="book-appointment-time-grid__item"
                                        :class="{
                                            'book-appointment-time-grid__item--selected':
                                                selectedTime === slot.time,
                                            'book-appointment-time-grid__item--available':
                                                slot.is_available,
                                            'book-appointment-time-grid__item--busy':
                                                !slot.is_available,
                                        }"
                                        :disabled="
                                            !slot.is_available || loadingSlots
                                        "
                                        :aria-selected="
                                            selectedTime === slot.time
                                        "
                                        @click="pickTime(slot.time)"
                                    >
                                        {{ slot.label }}
                                    </button>
                                </div>
                            </section>
                        </div>
                        <p class="book-appointment-modal__hint small mb-0 mt-2">
                            Intervalos de 30 em 30 minutos, das 08:00 às 19:30.
                        </p>
                        <p
                            v-if="availabilityError"
                            class="text-danger small mb-0 mt-2"
                        >
                            {{ availabilityError }}
                        </p>
                        <p
                            v-else-if="
                                !loadingSlots &&
                                selectedDate &&
                                !hasAvailableSlots
                            "
                            class="text-danger small mb-0 mt-2"
                        >
                            Nenhum horário livre neste dia. Escolha outra data.
                        </p>
                    </div>

                    <div class="book-appointment-modal__field">
                        <label class="form-label" for="booking-service">
                            Serviço
                        </label>
                        <input
                            id="booking-service"
                            v-model="form.service_label"
                            type="text"
                            class="form-control book-appointment-modal__input"
                            required
                        />
                    </div>

                    <div class="book-appointment-modal__field">
                        <label class="form-label" for="booking-notes">
                            Observações (opcional)
                        </label>
                        <textarea
                            id="booking-notes"
                            v-model="form.client_notes"
                            class="form-control book-appointment-modal__input"
                            rows="2"
                            maxlength="500"
                        />
                    </div>

                    <p
                        v-if="form.errors.scheduled_at"
                        class="text-danger small mb-0"
                    >
                        {{ form.errors.scheduled_at }}
                    </p>

                    <div class="d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="close"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="
                                form.processing ||
                                !selectedDate ||
                                !selectedTime ||
                                loadingSlots
                            "
                        >
                            Solicitar agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
