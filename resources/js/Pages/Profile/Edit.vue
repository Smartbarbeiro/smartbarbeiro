<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
            <h1 class="h4 mb-0 fw-semibold">Perfil</h1>
        </template>

        <div class="d-flex flex-column gap-4 profile-edit-stack">
            <div class="app-card p-4 app-card-profile-info">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :profile-url="profileUrl"
                    :is-barbershop="isBarbershop"
                    :barbershop-memberships="barbershopMemberships"
                    :acrylic-qr-order="acrylicQrOrder"
                />
            </div>

            <div
                v-if="isBarbershop && platformSubscriptionExempt"
                class="alert alert-info mb-0"
                role="status"
            >
                Sua conta foi isenta do plano da plataforma pelo administrador.
                Seu perfil público permanece ativo sem a assinatura mensal no
                Mercado Pago.
            </div>

            <div v-if="isBarbershop && servicePlans" class="app-card p-4">
                <ManageServicePlansForm :service-plans="servicePlans" />
            </div>

            <div v-if="isBarbershop" class="app-card p-4">
                <BarbershopPaymentStatusCard
                    :subscription-plan="subscriptionPlan"
                    :subscribe-url="subscribeUrl"
                    :mercadopago-configured="mercadopagoConfigured"
                    :active-subscribers-count="activeSubscribersCount"
                />
            </div>

            <div
                v-if="isBarbershop && (subscribers.length > 0 || subscriptionPlan?.is_enabled)"
                class="app-card p-4"
            >
                <SubscribersList :subscribers="subscribers" />
            </div>

            <div class="app-card p-4 app-form-panel">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
