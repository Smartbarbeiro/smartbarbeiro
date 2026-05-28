<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import ManageSubscriptionPlanForm from './Partials/ManageSubscriptionPlanForm.vue';
import SubscribersList from './Partials/SubscribersList.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

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
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Perfil</h1>
        </template>

        <div class="d-flex flex-column gap-4">
            <div class="app-card p-4">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :profile-url="profileUrl"
                    :is-barbershop="isBarbershop"
                    :barbershop-memberships="barbershopMemberships"
                />
            </div>

            <div v-if="isBarbershop" class="app-card p-4">
                <ManageSubscriptionPlanForm
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

            <div class="app-card p-4">
                <UpdatePasswordForm />
            </div>

            <div class="app-card p-4 app-form-panel">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
