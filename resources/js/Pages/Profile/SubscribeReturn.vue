<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    creator: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Subscription" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Subscription status
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="space-y-4 p-8">
                        <p v-if="subscription?.is_active" class="text-green-700">
                            Your subscription to {{ creator.name }} is active. You
                            now have access to their profile.
                        </p>
                        <p v-else class="text-amber-700">
                            We are confirming your payment with Mercado Pago. If
                            you completed checkout, access will unlock shortly.
                        </p>

                        <Link
                            :href="
                                route('profile.public', {
                                    username: creator.username,
                                })
                            "
                            class="inline-block rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                        >
                            Go to profile
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
