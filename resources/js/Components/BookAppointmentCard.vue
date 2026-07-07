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
});

const isOpen = ref(false);
const selectedDate = ref(new Date().toISOString().slice(0, 10));
const availableSlots = ref([]);
const loadingSlots = ref(false);

const form = useForm({
    scheduled_at: '',
    service_label: props.defaultServiceLabel,
    package_type: props.defaultPackageType,
    client_notes: '',
});

const selectedSlot = computed(() => {
    if (!form.scheduled_at) {
        return null;
    }

    return form.scheduled_at.slice(11, 16);
});

const loadSlots = async () => {
    loadingSlots.value = true;

    try {
        const response = await window.axios.get(
            route('barbershop.appointments.availability', {
                username: props.username,
            }),
            {
                params: { date: selectedDate.value },
            },
        );

        availableSlots.value = response.data.slots ?? [];
    } finally {
        loadingSlots.value = false;
    }
};

watch([isOpen, selectedDate], ([open]) => {
    if (open) {
        loadSlots();
    }
});

const pickSlot = (time) => {
    form.scheduled_at = `${selectedDate.value}T${time}:00`;
};

const submit = () => {
    form.post(
        route('barbershop.appointments.store', { username: props.username }),
        {
            preserveScroll: true,
            onSuccess: () => {
                isOpen.value = false;
                form.reset('client_notes');
                form.scheduled_at = '';
                form.service_label = props.defaultServiceLabel;
                form.package_type = props.defaultPackageType;
            },
        },
    );
};
</script>

<template>
    <div class="book-appointment-card">
        <button
            type="button"
            class="btn btn-primary"
            @click="isOpen = true"
        >
            <i class="bi bi-calendar-plus me-2"></i>
            Agendar horário
        </button>

        <div
            v-if="isOpen"
            class="book-appointment-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="book-appointment-title"
        >
            <div class="book-appointment-modal__backdrop" @click="isOpen = false" />

            <div class="book-appointment-modal__panel">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 id="book-appointment-title" class="h5 mb-1">
                            Agendar horário
                        </h2>
                        <p class="small text-secondary mb-0">
                            Escolha o dia e o horário. A barbearia confirma e
                            pode atribuir um funcionário.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Fechar"
                        @click="isOpen = false"
                    />
                </div>

                <form class="d-flex flex-column gap-3" @submit.prevent="submit">
                    <div>
                        <label class="form-label" for="booking-date">Dia</label>
                        <input
                            id="booking-date"
                            v-model="selectedDate"
                            type="date"
                            class="form-control"
                            required
                        />
                    </div>

                    <div>
                        <label class="form-label">Horário</label>
                        <div v-if="loadingSlots" class="small text-secondary">
                            Carregando horários...
                        </div>
                        <div v-else class="book-appointment-slots">
                            <button
                                v-for="slot in availableSlots"
                                :key="slot.time"
                                type="button"
                                class="book-appointment-slots__item"
                                :class="{
                                    'book-appointment-slots__item--selected':
                                        selectedSlot === slot.time,
                                    'book-appointment-slots__item--disabled':
                                        !slot.is_available,
                                }"
                                :disabled="!slot.is_available"
                                @click="pickSlot(slot.time)"
                            >
                                {{ slot.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="booking-service">Serviço</label>
                        <input
                            id="booking-service"
                            v-model="form.service_label"
                            type="text"
                            class="form-control"
                            required
                        />
                    </div>

                    <div>
                        <label class="form-label" for="booking-notes">
                            Observações (opcional)
                        </label>
                        <textarea
                            id="booking-notes"
                            v-model="form.client_notes"
                            class="form-control"
                            rows="2"
                            maxlength="500"
                        />
                    </div>

                    <p v-if="form.errors.scheduled_at" class="text-danger small mb-0">
                        {{ form.errors.scheduled_at }}
                    </p>

                    <div class="d-flex justify-content-end gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="isOpen = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="form.processing || !form.scheduled_at"
                        >
                            Solicitar agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
