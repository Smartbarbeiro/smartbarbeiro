<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
import { Head, Link } from '@inertiajs/vue3';

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

const statusClass = (status) => {
    if (status === 'authorized') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-gray-100 text-gray-700';
    if (status === 'pending') return 'bg-amber-100 text-amber-800';
    return 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My subscriptions" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                My subscriptions
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                <p
                    v-if="$page.props.flash?.status === 'subscription-cancelled'"
                    class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800"
                >
                    Subscription cancelled. You will lose access after the
                    current period unless you subscribe again.
                </p>

                <div
                    v-if="subscriptions.length === 0"
                    class="rounded-lg bg-white p-8 text-center shadow"
                >
                    <p class="text-gray-600">You have no subscriptions yet.</p>
                </div>

                <div
                    v-for="subscription in subscriptions"
                    :key="subscription.id"
                    class="rounded-lg bg-white p-6 shadow"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ subscription.creator.name }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                @{{ subscription.creator.username }}
                            </p>
                            <span
                                class="mt-2 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusClass(subscription.status)"
                            >
                                {{ subscription.status_label }}
                            </span>
                            <p
                                v-if="subscription.next_payment_date"
                                class="mt-2 text-sm text-gray-600"
                            >
                                Next payment:
                                {{
                                    new Date(
                                        subscription.next_payment_date,
                                    ).toLocaleDateString()
                                }}
                            </p>
                            <p
                                v-if="subscription.cancelled_at"
                                class="mt-1 text-sm text-gray-500"
                            >
                                Cancelled on
                                {{
                                    new Date(
                                        subscription.cancelled_at,
                                    ).toLocaleDateString()
                                }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 sm:items-end">
                            <Link
                                v-if="subscription.is_active"
                                :href="
                                    route('profile.public', {
                                        username: subscription.creator.username,
                                    })
                                "
                                class="text-sm text-indigo-600 underline"
                            >
                                View profile
                            </Link>

                            <CancelSubscriptionButton
                                v-if="subscription.is_cancellable"
                                :subscription-id="subscription.id"
                                :creator-name="subscription.creator.name"
                                compact
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
