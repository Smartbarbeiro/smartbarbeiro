<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import ServicePlanPaymentHistory from '@/Components/ServicePlanPaymentHistory.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    subscriptions: {
        type: Array,
        required: true,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
    isBarbershopClientsView: {
        type: Boolean,
        default: false,
    },
    isAdminPlatformSubscriptionsView: {
        type: Boolean,
        default: false,
    },
    platformPayingBarbershops: {
        type: Array,
        default: () => [],
    },
    platformRevenueSummary: {
        type: Object,
        default: null,
    },
    expectedMonthlyRevenue: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const pageTitle = computed(() => {
    if (page.props.auth.user?.is_administrator) {
        return 'Assinaturas';
    }

    if (page.props.auth.user?.is_barbershop) {
        return 'Clientes';
    }

    if (
        page.props.auth.user?.primary_barbershop_username &&
        !page.props.auth.user?.is_barbershop
    ) {
        return 'Plano';
    }

    return 'Minhas assinaturas';
});

const pageIcon = computed(() => {
    if (page.props.auth.user?.is_administrator) {
        return 'subscriptions';
    }

    if (page.props.auth.user?.is_barbershop) {
        return 'clients';
    }

    if (
        page.props.auth.user?.primary_barbershop_username &&
        !page.props.auth.user?.is_barbershop
    ) {
        return 'plan';
    }

    return 'subscriptions';
});

const pageDescription = computed(() => {
    if (page.props.auth.user?.is_administrator) {
        return 'Barbearias com assinatura ativa da plataforma no Mercado Pago.';
    }

    if (page.props.auth.user?.is_barbershop) {
        return 'Acompanhe assinaturas e planos ativos dos seus clientes.';
    }

    if (
        page.props.auth.user?.primary_barbershop_username &&
        !page.props.auth.user?.is_barbershop
    ) {
        return 'Detalhes do seu plano na barbearia preferida.';
    }

    return 'Planos e assinaturas vinculados à sua conta.';
});

const statusClass = (status) => {
    if (status === 'authorized') return 'badge bg-success';
    if (status === 'cancelled') return 'badge bg-secondary';
    if (status === 'pending') return 'badge bg-secondary';
    return 'badge bg-secondary';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="pageTitle" />

        <template #header>
            <DashboardPageHeader
                :icon="pageIcon"
                :title="pageTitle"
            />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardAlert
                :show="$page.props.flash?.status === 'subscription-cancelled'"
                variant="success"
            >
                Assinatura cancelada. Você perderá o acesso após o período atual,
                a menos que assine novamente.
            </DashboardAlert>

            <DashboardContentCard
                v-if="isAdminPlatformSubscriptionsView && platformRevenueSummary"
                icon="payment"
                title="Receita mensal da plataforma"
                description="Soma das assinaturas ativas de todas as barbearias pagantes."
            >
                <p class="display-6 fw-semibold mb-2">
                    {{ platformRevenueSummary.formatted_amount }}
                </p>
                <p class="text-secondary small mb-0">
                    {{ platformRevenueSummary.paying_barbershops_count }}
                    {{
                        platformRevenueSummary.paying_barbershops_count === 1
                            ? 'barbearia pagante'
                            : 'barbearias pagantes'
                    }}
                    · {{ platformRevenueSummary.plan_formatted_price }}/barbearia
                </p>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="isAdminPlatformSubscriptionsView && platformPayingBarbershops.length === 0"
                :icon="pageIcon"
                title="Barbearias pagantes"
                :description="pageDescription"
                centered
            >
                <p class="text-secondary mb-0">
                    Nenhuma barbearia com pagamento ativo da plataforma no momento.
                </p>
            </DashboardContentCard>

            <DashboardContentCard
                v-else-if="isAdminPlatformSubscriptionsView"
                icon="clients"
                title="Barbearias pagantes"
                :description="pageDescription"
            >
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Barbearia</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Valor/mês</th>
                                <th scope="col">Status</th>
                                <th scope="col">Próximo pagamento</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="barbershop in platformPayingBarbershops"
                                :key="barbershop.id"
                            >
                                <td>
                                    <p class="fw-medium mb-0">{{ barbershop.name }}</p>
                                    <p
                                        v-if="barbershop.username"
                                        class="text-secondary small mb-0"
                                    >
                                        @{{ barbershop.username }}
                                    </p>
                                    <span
                                        v-if="barbershop.is_frozen"
                                        class="badge bg-secondary mt-1"
                                    >
                                        Congelada
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    {{ barbershop.email }}
                                </td>
                                <td>{{ barbershop.formatted_monthly_amount }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        {{ barbershop.status_label }}
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    {{
                                        barbershop.next_payment_date
                                            ? new Date(
                                                  barbershop.next_payment_date,
                                              ).toLocaleDateString('pt-BR')
                                            : '—'
                                    }}
                                </td>
                                <td class="text-end">
                                    <Link
                                        :href="route('admin.users.edit', barbershop.id)"
                                        class="link-primary small"
                                    >
                                        Gerenciar
                                    </Link>
                                    <Link
                                        v-if="barbershop.profile_url"
                                        :href="barbershop.profile_url"
                                        class="link-primary small ms-3"
                                    >
                                        Ver perfil
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="isBarbershopClientsView && expectedMonthlyRevenue"
                icon="payment"
                title="Receita prevista do mês"
                description="Soma dos valores mensais dos planos ativos de todos os clientes."
            >
                <p class="display-6 fw-semibold mb-2">
                    {{ expectedMonthlyRevenue.formatted_amount }}
                </p>
                <p class="text-secondary small mb-0">
                    {{ expectedMonthlyRevenue.active_plans_count }}
                    {{
                        expectedMonthlyRevenue.active_plans_count === 1
                            ? 'plano ativo'
                            : 'planos ativos'
                    }}
                </p>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="!isAdminPlatformSubscriptionsView && subscriptions.length === 0"
                :icon="pageIcon"
                :title="pageTitle"
                :description="pageDescription"
                centered
            >
                <p class="text-secondary mb-0">
                    {{
                        isBarbershopClientsView
                            ? 'Nenhum cliente com plano cadastrado ainda.'
                            : 'Você ainda não tem assinaturas.'
                    }}
                </p>
            </DashboardContentCard>

            <DashboardContentCard
                v-else-if="!isAdminPlatformSubscriptionsView"
                :icon="pageIcon"
                :title="pageTitle"
                :description="pageDescription"
            >
                <div class="d-flex flex-column gap-3">
                    <article
                        v-for="subscription in subscriptions"
                        :key="subscription.id"
                        class="dashboard-content-card dashboard-content-card--nested"
                    >
                        <div
                            class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-sm-between gap-3"
                        >
                            <div>
                                <h3 class="h5 fw-semibold mb-1">
                                    {{
                                        isBarbershopClientsView
                                            ? subscription.subscriber?.name
                                            : subscription.creator.name
                                    }}
                                </h3>
                                <p
                                    v-if="isBarbershopClientsView"
                                    class="text-secondary small mb-2"
                                >
                                    {{ subscription.subscriber?.email }}
                                </p>
                                <p v-else class="text-secondary small mb-2">
                                    @{{ subscription.creator.username }}
                                </p>
                                <p
                                    v-if="subscription.kind === 'service_plan'"
                                    class="small mb-2"
                                >
                                    Plano: {{ subscription.package_label }}
                                    <span class="text-secondary">
                                        ({{ subscription.formatted_total }}/mês)
                                    </span>
                                </p>
                                <p
                                    v-else-if="
                                        isBarbershopClientsView &&
                                        subscription.formatted_total
                                    "
                                    class="small mb-2"
                                >
                                    Assinatura do perfil
                                    <span class="text-secondary">
                                        ({{ subscription.formatted_total }}/mês)
                                    </span>
                                </p>
                                <span
                                    class="badge"
                                    :class="statusClass(subscription.status)"
                                >
                                    {{ subscription.status_label }}
                                </span>
                                <p
                                    v-if="subscription.next_payment_date"
                                    class="small text-secondary mt-2 mb-0"
                                >
                                    Próximo pagamento:
                                    {{
                                        new Date(
                                            subscription.next_payment_date,
                                        ).toLocaleDateString('pt-BR')
                                    }}
                                </p>
                                <p
                                    v-if="subscription.cancelled_at"
                                    class="small text-secondary mb-0"
                                >
                                    Cancelada em
                                    {{
                                        new Date(
                                            subscription.cancelled_at,
                                        ).toLocaleDateString('pt-BR')
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="!isBarbershopClientsView"
                                class="d-flex flex-column gap-2 align-items-sm-end"
                            >
                                <Link
                                    v-if="subscription.is_active"
                                    :href="
                                        route('profile.public', {
                                            username: subscription.creator.username,
                                        })
                                    "
                                    class="link-primary small"
                                >
                                    Ver perfil
                                </Link>

                                <CancelSubscriptionButton
                                    v-if="subscription.is_cancellable"
                                    :subscription-id="subscription.id"
                                    :creator-name="subscription.creator.name"
                                    :destroy-route="
                                        subscription.kind === 'service_plan'
                                            ? route('service-plan-subscriptions.destroy', subscription.id)
                                            : route('subscriptions.destroy', subscription.id)
                                    "
                                    compact
                                />
                            </div>
                        </div>

                        <ServicePlanPaymentHistory
                            v-if="subscription.kind === 'service_plan'"
                            :payment-history="subscription.payment_history ?? []"
                        />
                    </article>
                </div>
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
