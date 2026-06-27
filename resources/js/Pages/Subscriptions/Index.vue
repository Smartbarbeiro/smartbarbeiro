<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
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
});

const page = usePage();
const pageTitle = computed(() => {
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
            <h1 class="h4 mb-0 fw-semibold">{{ pageTitle }}</h1>
        </template>

        <div class="d-flex flex-column gap-4">
            <div
                v-if="$page.props.flash?.status === 'subscription-cancelled'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Assinatura cancelada. Você perderá o acesso após o período atual,
                a menos que assine novamente.
            </div>

            <div
                v-if="subscriptions.length === 0"
                class="app-card p-4 text-center"
            >
                <p class="text-secondary mb-0">Você ainda não tem assinaturas.</p>
            </div>

            <div
                v-for="subscription in subscriptions"
                :key="subscription.id"
                class="app-card p-4"
            >
                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-sm-between gap-3"
                >
                    <div>
                        <h3 class="h5 fw-semibold mb-1">
                            {{ subscription.creator.name }}
                        </h3>
                        <p class="text-secondary small mb-2">
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

                    <div class="d-flex flex-column gap-2 align-items-sm-end">
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
