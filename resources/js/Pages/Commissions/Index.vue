<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    report: {
        type: Object,
        required: true,
    },
});

const visitMonth = (month) => {
    router.get(
        route('commissions.index'),
        { month },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const onMonthPick = (event) => {
    if (event.target.value) {
        visitMonth(event.target.value);
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Comissões" />

        <template #header>
            <DashboardPageHeader icon="platform-plan" title="Comissões" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardContentCard
                icon="platform-plan"
                title="Relatório de comissões"
                :description="`Serviços concluídos em ${report.month_label}.`"
            >
                <div class="barbershop-agenda-toolbar">
                    <div class="barbershop-agenda-toolbar__nav">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitMonth(report.prev_month)"
                        >
                            <i class="bi bi-chevron-left"></i>
                            Mês anterior
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="visitMonth(report.next_month)"
                        >
                            Próximo mês
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <div class="barbershop-agenda-toolbar__date">
                        <label class="form-label small mb-1" for="commission-month">
                            Escolher mês
                        </label>
                        <input
                            id="commission-month"
                            type="month"
                            class="form-control form-control-sm"
                            :value="report.month"
                            @change="onMonthPick"
                        />
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <p class="small text-secondary mb-1">Serviços concluídos</p>
                            <p class="h4 mb-0">{{ report.summary.completed_services }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <p class="small text-secondary mb-1">Valor dos serviços</p>
                            <p class="h4 mb-0">
                                {{ report.summary.formatted_total_service_amount }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <p class="small text-secondary mb-1">Total em comissões</p>
                            <p class="h4 mb-0">
                                {{ report.summary.formatted_total_commission_amount }}
                            </p>
                        </div>
                    </div>
                </div>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="report.employees.length === 0"
                icon="empty"
                title="Nenhuma comissão neste mês"
                description="Conclua agendamentos na agenda com um funcionário atribuído para gerar comissões."
                centered
            >
                <Link :href="route('agenda.index')" class="btn btn-primary btn-sm">
                    Ir para a agenda
                </Link>
            </DashboardContentCard>

            <DashboardContentCard
                v-else
                icon="clients"
                title="Por funcionário"
                description="Totais do mês agrupados por barbeiro."
            >
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Funcionário</th>
                                <th class="text-center">Serviços</th>
                                <th class="text-end">Valor serviços</th>
                                <th class="text-end">Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="employee in report.employees" :key="employee.employee_id ?? 'none'">
                                <td class="fw-semibold">{{ employee.employee_name }}</td>
                                <td class="text-center">{{ employee.completed_services }}</td>
                                <td class="text-end">
                                    {{ employee.formatted_total_service_amount }}
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ employee.formatted_total_commission_amount }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="report.appointments.length > 0"
                icon="subscriptions"
                title="Detalhamento"
                description="Cada serviço concluído com o valor e a comissão registrados."
            >
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th>Funcionário</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="appointment in report.appointments"
                                :key="appointment.id"
                            >
                                <td>
                                    {{ appointment.scheduled_date }}
                                    <span class="text-secondary">{{ appointment.scheduled_time }}</span>
                                </td>
                                <td>{{ appointment.client_name }}</td>
                                <td>{{ appointment.service_label }}</td>
                                <td>{{ appointment.employee_name ?? '—' }}</td>
                                <td class="text-end">
                                    {{ appointment.formatted_service_amount }}
                                </td>
                                <td class="text-end">
                                    {{ appointment.formatted_commission_amount }}
                                    <span class="text-secondary small d-block">
                                        {{ appointment.formatted_commission_percent }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
