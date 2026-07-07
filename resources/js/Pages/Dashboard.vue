<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarbershopNoSubscribersDashboard from '@/Components/BarbershopNoSubscribersDashboard.vue';
import BarbershopScheduleDashboard from '@/Components/BarbershopScheduleDashboard.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    isBarbershop: {
        type: Boolean,
        default: true,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    hasSubscribers: {
        type: Boolean,
        default: true,
    },
    hasConfiguredServicePlans: {
        type: Boolean,
        default: false,
    },
    barbershopAccountsCount: {
        type: Number,
        default: null,
    },
    profileUrl: {
        type: String,
        default: null,
    },
    subscribeUrl: {
        type: String,
        default: null,
    },
    storagePath: {
        type: String,
        default: null,
    },
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    activeSubscribersCount: {
        type: Number,
        default: 0,
    },
    barbershopMemberships: {
        type: Array,
        default: () => [],
    },
    acrylicQrOrder: {
        type: Object,
        default: null,
    },
    schedule: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Painel" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader
                v-if="!(isBarbershop && !hasSubscribers)"
                icon="dashboard"
                title="Painel"
            />
        </template>

        <BarbershopNoSubscribersDashboard
            v-if="isBarbershop && !hasSubscribers"
            :profile-url="profileUrl"
            :profile-username="$page.props.auth.user.username"
            :has-configured-service-plans="hasConfiguredServicePlans"
            :acrylic-qr-order="acrylicQrOrder"
        />

        <BarbershopScheduleDashboard
            v-else-if="isBarbershop && schedule && hasSubscribers"
            :schedule="schedule"
        />

        <DashboardContentCard
            v-else
            icon="dashboard"
            title="Painel"
            description="Visão geral da sua conta e atalhos principais."
        >
            <p class="mb-0">Você está conectado!</p>

            <div v-if="isBarbershop && profileUrl" class="mt-4">
                <p class="small fw-medium text-secondary mb-1">
                    Seu perfil público
                </p>
                <Link
                    :href="
                        route('profile.public', {
                            username: $page.props.auth.user.username,
                        })
                    "
                    class="link-primary"
                >
                    {{ profileUrl }}
                </Link>

                <ProfileQrCode
                    class="mt-3"
                    style="max-width: 28rem"
                    :url="profileUrl"
                    :filename="`${$page.props.auth.user.username}-profile`"
                    show-acrylic-order
                    :acrylic-order="acrylicQrOrder"
                />
            </div>

            <div v-if="isBarbershop && subscriptionPlan?.is_enabled" class="mt-4">
                <p class="small fw-medium text-secondary mb-1">
                    Perfil pago ({{ subscriptionPlan.formatted_price }}/mês)
                </p>
                <p class="small text-secondary mb-1">
                    Compartilhe este link para assinantes:
                </p>
                <p class="font-monospace small text-break link-primary mb-1">
                    {{ subscribeUrl }}
                </p>
                <p class="small text-secondary mb-2">
                    Assinantes ativos: {{ activeSubscribersCount }}
                </p>
                <Link :href="route('profile.edit')" class="link-primary small">
                    Gerenciar plano de assinatura
                </Link>
            </div>

            <div v-if="isBarbershop && storagePath" class="mt-4">
                <p class="small fw-medium text-secondary mb-1">
                    Sua pasta de armazenamento
                </p>
                <p class="font-monospace small text-secondary mb-0">
                    {{ storagePath }}
                </p>
            </div>

            <div v-if="isAdmin" class="mt-4">
                <p class="small text-secondary mb-1">Contas de barbearia</p>
                <p class="h3 fw-semibold mb-0">{{ barbershopAccountsCount }}</p>
            </div>

            <div v-else-if="!isBarbershop" class="mt-4">
                <p class="small fw-medium text-secondary mb-2">
                    Sua Barbearia Preferida:
                </p>
                <p
                    v-if="barbershopMemberships.length === 0"
                    class="small text-secondary mb-0"
                >
                    Você ainda não está cadastrado em nenhuma barbearia.
                </p>
                <ul v-else class="list-unstyled mb-0 d-flex flex-column gap-2">
                    <li
                        v-for="membership in barbershopMemberships"
                        :key="membership.id"
                    >
                        <Link
                            v-if="membership.barbershop.username"
                            :href="
                                route('profile.public', {
                                    username: membership.barbershop.username,
                                })
                            "
                            class="link-primary"
                        >
                            {{ membership.barbershop.name }}
                        </Link>
                        <span v-else>{{ membership.barbershop.name }}</span>
                    </li>
                </ul>
            </div>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
