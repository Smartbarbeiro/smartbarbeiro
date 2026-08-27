<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import ScheduleOwnerAppointmentModal from '@/Components/ScheduleOwnerAppointmentModal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    agenda: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const selectedSlotTime = ref(null);
const selectedSlotAssignees = ref(null);
const scheduleModalOpen = ref(false);

const statusMessage = computed(() => {
    if (page.props.flash?.status === 'appointment-updated') {
        return 'Agendamento atualizado com sucesso.';
    }

    if (page.props.flash?.status === 'appointment-created') {
        return 'Agendamento criado com sucesso.';
    }

    return null;
});

const visitDate = (date) => {
    router.get(
        route('agenda.index'),
        { date },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const actionForm = useForm({
    action: '',
    barbershop_employee_id: null,
});

const runAction = (appointmentId, action, employeeId = null) => {
    actionForm
        .transform(() => ({
            action,
            barbershop_employee_id: employeeId,
        }))
        .patch(route('agenda.update', appointmentId), {
            preserveScroll: true,
        });
};

const confirmAppointment = (appointment) => {
    const select = document.getElementById(`employee-${appointment.id}`);
    const employeeId = select?.value ? Number(select.value) : null;

    runAction(appointment.id, 'confirm', employeeId);
};

const onAssignEmployeeChange = (appointment, event) => {
    const employeeId = event.target.value ? Number(event.target.value) : null;
    const currentEmployeeId = appointment.employee?.id ?? null;

    if (employeeId === currentEmployeeId) {
        return;
    }

    runAction(appointment.id, 'assign', employeeId);
};

const onDatePick = (event) => {
    if (event.target.value) {
        visitDate(event.target.value);
    }
};

const openScheduleModal = (slot) => {
    selectedSlotTime.value = typeof slot === 'string' ? slot : slot.time;
    selectedSlotAssignees.value =
        typeof slot === 'string'
            ? null
            : (slot.available_assignee_ids ?? null);
    scheduleModalOpen.value = true;
};

const closeScheduleModal = () => {
    scheduleModalOpen.value = false;
    selectedSlotTime.value = null;
    selectedSlotAssignees.value = null;
};

const hexToRgba = (hex, alpha) => {
    if (!hex) {
        return null;
    }

    const normalized = hex.replace('#', '');
    const red = Number.parseInt(normalized.slice(0, 2), 16);
    const green = Number.parseInt(normalized.slice(2, 4), 16);
    const blue = Number.parseInt(normalized.slice(4, 6), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

const primaryAppointment = (slot) => slot.appointments[0] ?? null;

const slotEmployeeColor = (slot) =>
    primaryAppointment(slot)?.employee?.color ?? null;

const slotStyle = (slot) => {
    const color = slotEmployeeColor(slot);

    if (!color) {
        return {};
    }

    return {
        '--slot-accent': color,
        '--slot-fill': hexToRgba(color, 0.24),
        '--slot-border': hexToRgba(color, 0.55),
    };
};

const slotClasses = (slot) => ({
    'barbershop-agenda-slot--past': slot.is_past,
    'barbershop-agenda-slot--free':
        slot.is_available && slot.appointments.length === 0,
    'barbershop-agenda-slot--partial':
        slot.can_add_more && slot.appointments.length > 0,
    'barbershop-agenda-slot--clickable':
        slot.is_available && slot.appointments.length === 0,
    'barbershop-agenda-slot--assigned': !!slotEmployeeColor(slot),
    'barbershop-agenda-slot--booked': slot.appointments.length > 0,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Agenda" />

        <template #header>
            <DashboardPageHeader icon="dashboard" title="Agenda" />
        </template>

        <div class="d-flex flex-column gap-4 barbershop-agenda-page">
            <DashboardAlert v-if="statusMessage" variant="success" :show="true">
                {{ statusMessage }}
            </DashboardAlert>

            <DashboardContentCard
                icon="dashboard"
                title="Agenda inteligente"
                :description="agenda.formatted_date"
            >
                <div class="barbershop-agenda-toolbar">
                    <div class="barbershop-agenda-toolbar__nav">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitDate(agenda.prev_week_date)"
                        >
                            <i class="bi bi-chevron-double-left"></i>
                            Semana anterior
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitDate(agenda.prev_day_date)"
                        >
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitDate(agenda.next_day_date)"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitDate(agenda.next_week_date)"
                        >
                            Próxima semana
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                    </div>

                    <div class="barbershop-agenda-toolbar__date">
                        <label class="form-label small mb-1" for="agenda-date">
                            Escolher dia
                        </label>
                        <input
                            id="agenda-date"
                            type="date"
                            class="form-control form-control-sm"
                            :value="agenda.date"
                            @change="onDatePick"
                        />
                    </div>
                </div>

                <div class="barbershop-agenda-week">
                    <button
                        v-for="day in agenda.week_days"
                        :key="day.date"
                        type="button"
                        class="barbershop-agenda-week__day"
                        :class="{
                            'barbershop-agenda-week__day--selected': day.is_selected,
                            'barbershop-agenda-week__day--today': day.is_today,
                        }"
                        @click="visitDate(day.date)"
                    >
                        <span class="barbershop-agenda-week__weekday">{{
                            day.weekday_label
                        }}</span>
                        <span class="barbershop-agenda-week__number">{{
                            day.day
                        }}</span>
                        <span
                            v-if="day.appointment_count > 0"
                            class="barbershop-agenda-week__badge"
                        >
                            {{ day.appointment_count }}
                        </span>
                    </button>
                </div>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="agenda.pending_appointments.length > 0"
                icon="status"
                title="Solicitações pendentes"
                :description="`${agenda.pending_appointments.length} aguardando confirmação.`"
            >
                <div class="d-flex flex-column gap-3">
                    <article
                        v-for="appointment in agenda.pending_appointments"
                        :key="`pending-${appointment.id}`"
                        class="barbershop-agenda-request"
                    >
                        <div>
                            <p class="mb-1 fw-semibold">
                                {{ appointment.client.name }}
                            </p>
                            <p class="mb-1 small text-secondary">
                                {{
                                    appointment.formatted_scheduled_at
                                        || `${appointment.scheduled_time} · ${appointment.scheduled_date}`
                                }}
                                · {{ appointment.service_label }}
                            </p>
                            <p
                                v-if="appointment.client_notes"
                                class="mb-0 small"
                            >
                                {{ appointment.client_notes }}
                            </p>
                        </div>

                        <div class="barbershop-agenda-request__actions">
                            <select
                                :id="`employee-${appointment.id}`"
                                class="form-select form-select-sm"
                            >
                                <option value="">Proprietário</option>
                                <option
                                    v-for="employee in agenda.employees.filter((item) => item.is_active)"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                :disabled="actionForm.processing"
                                @click="confirmAppointment(appointment)"
                            >
                                Confirmar
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="actionForm.processing"
                                @click="runAction(appointment.id, 'reject')"
                            >
                                Recusar
                            </button>
                        </div>
                    </article>
                </div>
            </DashboardContentCard>

            <DashboardContentCard
                icon="dashboard"
                title="Horários do dia"
                description="Clique em um horário livre para agendar. Grade de 30 em 30 minutos, das 08:00 às 20:00."
            >
                <div class="barbershop-agenda-slots">
                    <article
                        v-for="slot in agenda.slots"
                        :key="slot.time"
                        class="barbershop-agenda-slot"
                        :class="slotClasses(slot)"
                        :style="slotStyle(slot)"
                        @click="
                            slot.is_available && slot.appointments.length === 0
                                ? openScheduleModal(slot)
                                : null
                        "
                    >
                        <div class="barbershop-agenda-slot__time">
                            <span class="barbershop-agenda-slot__time-label">{{
                                slot.label
                            }}</span>
                        </div>

                        <div class="barbershop-agenda-slot__content">
                            <template v-if="slot.appointments.length === 0">
                                <p class="barbershop-agenda-slot__empty">
                                    {{
                                        slot.is_past
                                            ? 'Horário passado'
                                            : 'Livre'
                                    }}
                                </p>
                            </template>

                            <div
                                v-for="appointment in slot.appointments"
                                :key="appointment.id"
                                class="barbershop-agenda-appointment"
                                @click.stop
                            >
                                <div class="barbershop-agenda-appointment__header">
                                    <div>
                                        <p class="barbershop-agenda-appointment__client mb-0">
                                            {{ appointment.client.name }}
                                        </p>
                                        <p class="barbershop-agenda-appointment__service mb-0">
                                            {{ appointment.service_label }}
                                            <span
                                                v-if="appointment.performer_name"
                                                class="barbershop-agenda-appointment__performer"
                                            >
                                                · {{ appointment.performer_name }}
                                            </span>
                                        </p>
                                    </div>
                                    <span
                                        class="barbershop-agenda-appointment__status"
                                        :class="{
                                            'barbershop-agenda-appointment__status--pending':
                                                appointment.status === 'pending',
                                            'barbershop-agenda-appointment__status--confirmed':
                                                appointment.status === 'confirmed',
                                            'barbershop-agenda-appointment__status--done':
                                                appointment.status === 'completed' ||
                                                appointment.status === 'cancelled' ||
                                                appointment.status === 'rejected',
                                        }"
                                    >
                                        {{ appointment.status_label }}
                                    </span>
                                </div>

                                <div
                                    v-if="appointment.status === 'confirmed'"
                                    class="barbershop-agenda-appointment__actions"
                                >
                                    <select
                                        class="form-select form-select-sm"
                                        :value="appointment.employee?.id ?? ''"
                                        :disabled="actionForm.processing"
                                        @change="onAssignEmployeeChange(appointment, $event)"
                                    >
                                        <option value="">Proprietário</option>
                                        <option
                                            v-for="employee in agenda.employees.filter((item) => item.is_active)"
                                            :key="`assign-${appointment.id}-${employee.id}`"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }}
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        :disabled="actionForm.processing"
                                        @click="runAction(appointment.id, 'complete')"
                                    >
                                        Concluir
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="actionForm.processing"
                                        @click="runAction(appointment.id, 'cancel')"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            v-if="slot.can_add_more && slot.appointments.length > 0"
                            type="button"
                            class="barbershop-agenda-slot__add"
                            title="Agendar outro barbeiro neste horário"
                            aria-label="Agendar outro barbeiro neste horário"
                            @click.stop="openScheduleModal(slot)"
                        >
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
            </DashboardContentCard>
        </div>

        <ScheduleOwnerAppointmentModal
            :show="scheduleModalOpen"
            :date="agenda.date"
            :time="selectedSlotTime"
            :owner="agenda.owner"
            :employees="agenda.employees"
            :available-assignee-ids="selectedSlotAssignees"
            :services="agenda.services"
            :clients="agenda.clients"
            @close="closeScheduleModal"
        />
    </AuthenticatedLayout>
</template>
