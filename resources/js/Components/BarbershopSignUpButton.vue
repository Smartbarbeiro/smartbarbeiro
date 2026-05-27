<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
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
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    hasSignedUp: {
        type: Boolean,
        default: false,
    },
    requiresPayment: {
        type: Boolean,
        default: false,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
});

const registerUrl = computed(() =>
    route('register', {
        redirect: `/barbearias/${props.profile.username}`,
    }),
);

const loginUrl = computed(() =>
    route('login', {
        redirect: `/barbearias/${props.profile.username}`,
    }),
);
</script>

<template>
    <div v-if="!isOwner">
        <div
            v-if="hasSignedUp"
            class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
        >
            You are signed up at this barbershop.
        </div>

        <div v-else class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
            <Link
                v-if="!isAuthenticated"
                :href="registerUrl"
                class="inline-flex"
            >
                <PrimaryButton>Sign up at this barbershop</PrimaryButton>
            </Link>

            <template v-else-if="requiresPayment">
                <Link
                    v-if="mercadopagoConfigured"
                    :href="
                        route('profile.subscribe', {
                            username: profile.username,
                        })
                    "
                    method="post"
                    as="button"
                    class="inline-flex"
                >
                    <PrimaryButton>Sign up at this barbershop</PrimaryButton>
                </Link>
                <p v-else class="text-sm text-amber-700">
                    Paid sign-up is unavailable because payments are not
                    configured.
                </p>
            </template>

            <Link
                v-else
                :href="
                    route('barbershop.signup', {
                        username: profile.username,
                    })
                "
                method="post"
                as="button"
                class="inline-flex"
            >
                <PrimaryButton>Sign up at this barbershop</PrimaryButton>
            </Link>

            <Link
                v-if="!isAuthenticated"
                :href="loginUrl"
                class="text-sm text-gray-600 underline hover:text-gray-900"
            >
                Already have an account? Log in
            </Link>
        </div>
    </div>
</template>
