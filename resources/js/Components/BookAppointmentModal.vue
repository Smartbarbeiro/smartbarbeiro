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

const todayDate = () => formatDateInput(new Date());

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
        maxDate.value = data.max_date ?? '';
        selectedDate.value = data.date ?? date;
        selectedTime.value = '';
        syncScheduledAt();
    } catch {
        availableSlots.value = buildDefaultSlots();
        availabilityError.value =
            'Não foi possível atualizar a disponibilidade. Os horários padrão estão exibidos.';
    } finally {
        loadingSlots.value = false;
    }
};

const resetPicker = () => {
    const today = todayDate();

    minDate.value = today;
    maxDate.value = '';
    selectedDate.value = today;
    selectedTime.value = '';
    availableSlots.value = buildDefaultSlots();
    availabilityError.value = null;
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

const onDateChange = () => {
    selectedTime.value = '';
    syncScheduledAt();
    loadSlotsForDate(selectedDate.value);
};

const pickTime = (time) => {
    const slot = availableSlots.value.find((item) => item.time === time);

    if (!slot?.is_available) {
        return;
    }

    selectedTime.value = time;
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
                        <label class="form-label" for="booking-date">Dia</label>
                        <input
                            id="booking-date"
                            v-model="selectedDate"
                            type="date"
                            class="form-control book-appointment-modal__input"
                            :min="minDate || todayDate()"
                            :max="maxDate || undefined"
                            :disabled="loadingSlots"
                            required
                            @change="onDateChange"
                            @input="onDateChange"
                        />
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
                            class="book-appointment-time-grid"
                            role="listbox"
                            aria-label="Horários disponíveis"
                        >
                            <button
                                v-for="slot in availableSlots"
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
                                :disabled="!slot.is_available || loadingSlots"
                                :aria-selected="selectedTime === slot.time"
                                @click="pickTime(slot.time)"
                            >
                                {{ slot.label }}
                            </button>
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
