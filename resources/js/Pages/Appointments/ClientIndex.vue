<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BookAppointmentFab from '@/Components/BookAppointmentFab.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    barbershop: {
        type: Object,
        required: true,
    },
    appointments: {
        type: Array,
        default: () => [],
    },
    booking: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const cancellingId = ref(null);

const statusMessage = computed(() => {
    if (page.props.flash?.status === 'appointment-requested') {
        return 'Solicitação enviada! A barbearia vai confirmar ou recusar em breve.';
    }

    if (page.props.flash?.status === 'appointment-cancelled') {
        return 'Agendamento cancelado.';
    }

    return page.props.flash?.statusMessage ?? null;
});

const statusBadgeClass = (status) => {
    if (status === 'confirmed') {
        return 'bg-success';
    }

    if (status === 'pending') {
        return 'bg-warning text-dark';
    }

    if (status === 'completed') {
        return 'bg-secondary';
    }

    if (status === 'cancelled' || status === 'rejected') {
        return 'bg-secondary';
    }

    return 'bg-secondary';
};

const cancelAppointment = (appointment) => {
    if (
        !window.confirm(
            appointment.status === 'pending'
                ? 'Cancelar esta solicitação de agendamento?'
                : 'Cancelar este agendamento confirmado?',
        )
    ) {
        return;
    }

    cancellingId.value = appointment.id;

    router.patch(
        route('client.appointments.cancel', appointment.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancellingId.value = null;
            },
        },
    );
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Agendamentos" />

        <template #header>
            <DashboardPageHeader icon="journal-bookmark" title="Agendamentos" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardAlert
                v-if="statusMessage"
                variant="success"
                :show="true"
            >
                {{ statusMessage }}
            </DashboardAlert>

            <DashboardContentCard
                icon="journal-bookmark"
                :title="barbershop.name"
                description="Seus horários solicitados e confirmados."
            >
                <p v-if="appointments.length === 0" class="text-secondary mb-0">
                    Você ainda não tem agendamentos. Toque em
                    <strong>Agendar</strong> para escolher dia e horário.
                </p>

                <div v-else class="d-flex flex-column gap-3">
                    <article
                        v-for="appointment in appointments"
                        :key="appointment.id"
                        class="client-appointment-card"
                    >
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <p class="mb-1 fw-semibold">
                                    {{ appointment.service_label }}
                                </p>
                                <p class="mb-1 small text-secondary">
                                    {{ appointment.formatted_scheduled_at }}
                                </p>
                                <p
                                    v-if="appointment.performer_name"
                                    class="mb-0 small"
                                >
                                    Com {{ appointment.performer_name }}
                                </p>
                                <p
                                    v-if="appointment.client_notes"
                                    class="mb-0 small mt-2"
                                >
                                    {{ appointment.client_notes }}
                                </p>
                            </div>
                            <span
                                class="badge flex-shrink-0"
                                :class="statusBadgeClass(appointment.status)"
                            >
                                {{ appointment.status_label }}
                            </span>
                        </div>
                        <div
                            v-if="appointment.is_cancellable"
                            class="d-flex justify-content-end mt-3"
                        >
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="cancellingId === appointment.id"
                                @click="cancelAppointment(appointment)"
                            >
                                {{
                                    cancellingId === appointment.id
                                        ? 'Cancelando...'
                                        : 'Cancelar agendamento'
                                }}
                            </button>
                        </div>
                    </article>
                </div>
            </DashboardContentCard>
        </div>

        <BookAppointmentFab
            :username="booking.username"
            :default-service-label="booking.defaultServiceLabel"
            :default-package-type="booking.defaultPackageType"
        />
    </AuthenticatedLayout>
</template>
