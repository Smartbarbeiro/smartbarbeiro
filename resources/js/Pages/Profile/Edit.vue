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
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile-url="profileUrl"
                        :is-barbershop="isBarbershop"
                        :barbershop-memberships="barbershopMemberships"
                        class="max-w-xl"
                    />
                </div>

                <div
                    v-if="isBarbershop"
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <ManageSubscriptionPlanForm
                        :subscription-plan="subscriptionPlan"
                        :subscribe-url="subscribeUrl"
                        :mercadopago-configured="mercadopagoConfigured"
                        :active-subscribers-count="activeSubscribersCount"
                        class="max-w-xl"
                    />
                </div>

                <div
                    v-if="isBarbershop && (subscribers.length > 0 || subscriptionPlan?.is_enabled)"
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <SubscribersList
                        :subscribers="subscribers"
                        class="max-w-4xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
