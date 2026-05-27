<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BarbershopSignUpButton from '@/Components/BarbershopSignUpButton.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
    canView: {
        type: Boolean,
        default: true,
    },
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    subscribeUrl: {
        type: String,
        required: true,
    },
    hasActiveSubscription: {
        type: Boolean,
        default: false,
    },
    activeSubscription: {
        type: Object,
        default: null,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
    requiresPayment: {
        type: Boolean,
        default: false,
    },
    hasSignedUp: {
        type: Boolean,
        default: false,
    },
});

const isAuthenticated = computed(() => !!usePage().props.auth.user);

const flashStatus = computed(() => usePage().props.flash?.status);

const showPaywall = computed(
    () =>
        props.subscriptionPlan?.is_enabled &&
        !props.canView &&
        !props.isOwner,
);
</script>

<template>
    <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
        <Head :title="profile.name" />

        <template v-if="isAuthenticated" #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ profile.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div
                    v-if="showPaywall"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-8 text-center">
                        <ProfileAvatar
                            class="mx-auto"
                            :name="profile.name"
                            :photo-url="profile.profile_photo_url"
                            size="xl"
                        />
                        <p class="mt-4 text-sm uppercase tracking-wide text-gray-500">
                            Subscribers only
                        </p>
                        <h1 class="mt-2 text-2xl font-bold text-gray-900">
                            {{ subscriptionPlan.title }}
                        </h1>
                        <p class="mt-1 text-gray-600">@{{ profile.username }}</p>
                        <p class="mt-2 text-3xl font-semibold text-indigo-600">
                            {{ subscriptionPlan.formatted_price }}
                            <span class="text-base font-normal text-gray-500"
                                >/ month</span
                            >
                        </p>
                        <p
                            v-if="subscriptionPlan.description"
                            class="mx-auto mt-4 max-w-md text-gray-600"
                        >
                            {{ subscriptionPlan.description }}
                        </p>

                        <div class="mt-8 flex flex-col items-center gap-3">
                            <BarbershopSignUpButton
                                :profile="profile"
                                :is-owner="isOwner"
                                :is-authenticated="isAuthenticated"
                                :has-signed-up="hasSignedUp"
                                :requires-payment="requiresPayment"
                                :mercadopago-configured="mercadopagoConfigured"
                            />

                            <p class="text-xs text-gray-500">
                                Share link:
                                <span class="font-mono">{{ subscribeUrl }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-8">
                        <ProfileAvatar
                            :name="profile.name"
                            :photo-url="profile.profile_photo_url"
                            size="xl"
                        />
                        <p class="mt-4 text-sm uppercase tracking-wide text-gray-500">
                            Public profile
                        </p>
                        <h1 class="mt-2 text-3xl font-bold text-gray-900">
                            {{ profile.name }}
                        </h1>
                        <p class="mt-1 text-gray-600">@{{ profile.username }}</p>
                        <p class="mt-4 text-sm text-gray-500">
                            Member since {{ profile.member_since }}
                        </p>

                        <div
                            v-if="flashStatus === 'barbershop-signup-success'"
                            class="mt-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
                        >
                            You are signed up at this barbershop.
                        </div>

                        <BarbershopSignUpButton
                            class="mt-6"
                            :profile="profile"
                            :is-owner="isOwner"
                            :is-authenticated="isAuthenticated"
                            :has-signed-up="hasSignedUp"
                            :requires-payment="requiresPayment"
                            :mercadopago-configured="mercadopagoConfigured"
                        />

                        <div
                            v-if="hasActiveSubscription && activeSubscription"
                            class="mt-4 rounded-md border border-green-200 bg-green-50 p-4"
                        >
                            <p class="text-sm font-medium text-green-800">
                                You have an active subscription.
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <CancelSubscriptionButton
                                    v-if="activeSubscription.is_cancellable"
                                    :subscription-id="activeSubscription.id"
                                    :creator-name="profile.name"
                                    compact
                                />
                                <Link
                                    :href="route('subscriptions.index')"
                                    class="text-sm text-gray-600 underline"
                                >
                                    Manage all subscriptions
                                </Link>
                            </div>
                        </div>

                        <p
                            v-if="isOwner && subscriptionPlan?.is_enabled"
                            class="mt-4 rounded-md border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-900"
                        >
                            Paid access is on ({{ subscriptionPlan.formatted_price }}/mo).
                            Share your subscribe link:
                            <span class="break-all font-mono text-xs">{{
                                subscribeUrl
                            }}</span>
                        </p>

                        <p class="mt-6 text-sm text-gray-600">
                            Profile link:
                            <span class="font-mono text-gray-800">{{
                                profile.profile_url
                            }}</span>
                        </p>

                        <ProfileQrCode
                            class="mt-4 max-w-md"
                            :url="profile.profile_url"
                            :filename="`${profile.username}-profile`"
                        />

                        <div v-if="isOwner" class="mt-8 flex flex-wrap gap-4">
                            <Link
                                :href="route('profile.edit')"
                                class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                            >
                                Edit profile
                            </Link>
                            <Link
                                :href="route('dashboard')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
