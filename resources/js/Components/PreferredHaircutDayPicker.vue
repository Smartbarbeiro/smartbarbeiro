<script setup>
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    barbershopUsername: {
        type: String,
        required: true,
    },
    preferredHaircutDay: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    preferred_haircut_day: props.preferredHaircutDay,
});

const viewDate = ref(new Date());

const weekdayLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

const currentMonthStart = computed(() => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), 1);
});

const canGoToPreviousMonth = computed(() => {
    const viewed = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth(),
        1,
    );

    return viewed > currentMonthStart.value;
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric',
    }),
);

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
    viewDate.value = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth() + 1,
        1,
    );
};

const today = computed(() => {
    const now = new Date();

    return {
        day: now.getDate(),
        month: now.getMonth(),
        year: now.getFullYear(),
    };
});

const calendarDates = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const startWeekday = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = [];

    for (let index = 0; index < startWeekday; index += 1) {
        cells.push({
            type: 'empty',
            key: `empty-${index}`,
        });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        cells.push({
            type: 'current',
            day,
            key: `day-${day}`,
            isToday:
                day === today.value.day &&
                month === today.value.month &&
                year === today.value.year,
            isSelected: form.preferred_haircut_day === day,
        });
    }

    return cells;
});

const selectedDayLabel = computed(() => {
    if (!form.preferred_haircut_day) {
        return null;
    }

    return `Dia ${form.preferred_haircut_day} de cada mês`;
});

const selectDay = (day) => {
    if (form.processing) {
        return;
    }

    form.preferred_haircut_day = day;
    form.clearErrors('preferred_haircut_day');
    submit();
};

const close = () => {
    if (form.processing) {
        return;
    }

    emit('close');
};

const submit = () => {
    if (!form.preferred_haircut_day || form.processing) {
        if (!form.preferred_haircut_day) {
            form.setError(
                'preferred_haircut_day',
                'Selecione um dia do mês no calendário.',
            );
        }

        return;
    }

    form.patch(
        route('barbershop.preferred-haircut-day.update', {
            username: props.barbershopUsername,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                close();
            },
        },
    );
};

const resetPicker = () => {
    const now = new Date();

    if (props.preferredHaircutDay) {
        form.preferred_haircut_day = props.preferredHaircutDay;
        viewDate.value = new Date(
            now.getFullYear(),
            now.getMonth(),
            Math.min(
                props.preferredHaircutDay,
                new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate(),
            ),
        );

        return;
    }

    form.preferred_haircut_day = null;
    viewDate.value = new Date();
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            resetPicker();
        }
    },
);

watch(
    () => props.preferredHaircutDay,
    (day) => {
        if (day) {
            form.preferred_haircut_day = day;
        }
    },
);
</script>

<template>
    <Modal
        :show="show"
        max-width="md"
        :closeable="closeable"
        @close="close"
    >
        <form class="preferred-haircut-picker p-4" @submit.prevent="submit">
            <h2 class="h5 fw-semibold mb-2">Dia preferido para o corte</h2>

            <p class="text-secondary small mb-4">
                Toque no dia do mês em que prefere fazer o corte nesta
                barbearia. A preferência é salva automaticamente.
            </p>

            <div class="preferred-haircut-calendar">
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
                        aria-label="Próximo mês"
                        @click="goToNextMonth"
                    >
                        ›
                    </button>
                </div>

                <ul class="preferred-haircut-calendar__days">
                    <li
                        v-for="label in weekdayLabels"
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
                        }"
                    >
                        <button
                            v-if="cell.type === 'current'"
                            type="button"
                            class="preferred-haircut-calendar__date-btn"
                            :aria-label="`Dia ${cell.day}`"
                            :aria-pressed="cell.isSelected"
                            :disabled="form.processing"
                            @click="selectDay(cell.day)"
                        >
                            {{ cell.day }}
                        </button>
                    </li>
                </ul>
            </div>

            <p
                v-if="form.processing"
                class="preferred-haircut-calendar__selection small mb-0 mt-3 text-secondary"
            >
                Salvando preferência...
            </p>
            <p
                v-else-if="selectedDayLabel"
                class="preferred-haircut-calendar__selection small mb-0 mt-3"
            >
                {{ selectedDayLabel }}
            </p>

            <InputError
                class="mt-2"
                :message="form.errors.preferred_haircut_day"
            />

            <div
                v-if="closeable"
                class="d-flex justify-content-end mt-4"
            >
                <SecondaryButton
                    type="button"
                    :disabled="form.processing"
                    @click="close"
                >
                    Cancelar
                </SecondaryButton>
            </div>
        </form>
    </Modal>
</template>
