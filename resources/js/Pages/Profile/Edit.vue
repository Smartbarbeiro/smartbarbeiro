<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import BarbershopPaymentStatusCard from './Partials/BarbershopPaymentStatusCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import ManageServicePlansForm from './Partials/ManageServicePlansForm.vue';
import SubscribersList from './Partials/SubscribersList.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    isBarbershop: {
        type: Boolean,
        default: true,
    },
    profileUrl: {
        type: String,
        default: null,
    },
    subscribeUrl: {
        type: String,
        default: null,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    activeSubscribersCount: {
        type: Number,
        default: 0,
    },
    subscribers: {
        type: Array,
        default: () => [],
    },
    barbershopMemberships: {
        type: Array,
        default: () => [],
    },
    servicePlans: {
        type: Object,
        default: null,
    },
    acrylicQrOrder: {
        type: Object,
        default: null,
    },
    platformSubscriptionExempt: {
        type: Boolean,
        default: false,
    },
});

onMounted(() => {
    if (window.location.hash === '#planos-de-servico') {
        document
            .getElementById('planos-de-servico')
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="profile" title="Perfil" />
        </template>

        <div class="d-flex flex-column gap-4 profile-edit-stack">
            <DashboardContentCard
                icon="profile-info"
                title="Informações do perfil"
                description="Atualize nome, e-mail, foto e dados da sua conta."
                card-class="app-card-profile-info"
            >
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :profile-url="profileUrl"
                    :is-barbershop="isBarbershop"
                    :barbershop-memberships="barbershopMemberships"
                    :acrylic-qr-order="acrylicQrOrder"
                />
            </DashboardContentCard>

            <div
                v-if="isBarbershop && platformSubscriptionExempt"
                class="alert alert-info mb-0"
                role="status"
            >
                Sua conta foi isenta do plano da plataforma pelo administrador.
                Seu perfil público permanece ativo sem a assinatura mensal no
                Mercado Pago.
            </div>

            <DashboardContentCard
                v-if="isBarbershop && servicePlans"
                icon="service-plans"
                title="Planos de serviço"
                description="Monte pacotes e opcionais para seus clientes assinarem."
            >
                <ManageServicePlansForm :service-plans="servicePlans" />
            </DashboardContentCard>

            <DashboardContentCard
                v-if="isBarbershop"
                icon="payment"
                title="Status do pagamento"
                description="Acompanhe Mercado Pago e assinaturas do perfil pago."
            >
                <BarbershopPaymentStatusCard
                    :subscription-plan="subscriptionPlan"
                    :subscribe-url="subscribeUrl"
                    :mercadopago-configured="mercadopagoConfigured"
                    :active-subscribers-count="activeSubscribersCount"
                />
            </DashboardContentCard>

            <DashboardContentCard
                v-if="isBarbershop && (subscribers.length > 0 || subscriptionPlan?.is_enabled)"
                icon="subscribers"
                title="Assinantes"
                description="Clientes com plano de assinatura ativo no seu perfil."
            >
                <SubscribersList :subscribers="subscribers" />
            </DashboardContentCard>

            <DashboardContentCard
                icon="delete-account"
                title="Excluir conta"
                description="Remova permanentemente sua conta e todos os dados."
                card-class="app-form-panel"
            >
                <DeleteUserForm />
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
