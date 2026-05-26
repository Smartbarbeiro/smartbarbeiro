<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    profileUrl: {
        type: String,
        required: true,
    },
    subscribeUrl: {
        type: String,
        required: true,
    },
    storagePath: {
        type: String,
        required: true,
    },
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    activeSubscribersCount: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="space-y-4 p-6 text-gray-900">
                        <p>You're logged in!</p>

                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                Your public profile
                            </p>
                            <Link
                                :href="
                                    route('profile.public', {
                                        username: $page.props.auth.user.username,
                                    })
                                "
                                class="text-indigo-600 underline hover:text-indigo-500"
                            >
                                {{ profileUrl }}
                            </Link>

                            <ProfileQrCode
                                class="mt-4 max-w-md"
                                :url="profileUrl"
                                :filename="`${$page.props.auth.user.username}-profile`"
                            />
                        </div>

                        <div v-if="subscriptionPlan?.is_enabled">
                            <p class="text-sm font-medium text-gray-700">
                                Paid profile ({{ subscriptionPlan.formatted_price }}/mo)
                            </p>
                            <p class="text-sm text-gray-600">
                                Share this link for subscribers:
                            </p>
                            <p class="break-all font-mono text-sm text-indigo-600">
                                {{ subscribeUrl }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                Active subscribers: {{ activeSubscribersCount }}
                            </p>
                            <Link
                                :href="route('profile.edit')"
                                class="mt-2 inline-block text-sm text-indigo-600 underline"
                            >
                                Manage subscription plan
                            </Link>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                Your storage folder
                            </p>
                            <p class="font-mono text-sm text-gray-600">
                                {{ storagePath }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
