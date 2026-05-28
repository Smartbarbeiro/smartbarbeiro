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
        <Head :title="isOwner && isAuthenticated ? 'Barbearia' : profile.name" />

        <template v-if="isAuthenticated" #header>
            <h1 class="h4 mb-0 fw-semibold">
                {{ isOwner ? 'Barbearia' : profile.name }}
            </h1>
        </template>

        <div
            :class="
                isAuthenticated
                    ? 'app-card p-4 mx-auto'
                    : ''
            "
            :style="isAuthenticated ? { maxWidth: '48rem' } : undefined"
        >
            <div v-if="showPaywall" class="text-center">
                <ProfileAvatar
                    class="mx-auto d-block"
                    :name="profile.name"
                    :photo-url="profile.profile_photo_url"
                    size="xl"
                />
                <p class="small text-uppercase text-secondary mt-3 mb-0">
                    Apenas assinantes
                </p>
                <h1 class="h3 fw-bold mt-2 mb-1">
                    {{ subscriptionPlan.title }}
                </h1>
                <p class="text-secondary mb-0">@{{ profile.username }}</p>
                <p class="display-6 fw-semibold text-primary mt-2 mb-0">
                    {{ subscriptionPlan.formatted_price }}
                    <span class="fs-6 fw-normal text-secondary">/ mês</span>
                </p>
                <p
                    v-if="subscriptionPlan.description"
                    class="text-secondary mx-auto mt-3 mb-0"
                    style="max-width: 28rem"
                >
                    {{ subscriptionPlan.description }}
                </p>

                <div class="d-flex flex-column align-items-center gap-3 mt-4">
                    <BarbershopSignUpButton
                        :profile="profile"
                        :is-owner="isOwner"
                        :is-authenticated="isAuthenticated"
                        :has-signed-up="hasSignedUp"
                        :requires-payment="requiresPayment"
                        :mercadopago-configured="mercadopagoConfigured"
                    />

                    <p class="small text-secondary mb-0">
                        Link para compartilhar:
                        <span class="font-monospace">{{ subscribeUrl }}</span>
                    </p>
                </div>
            </div>

            <div v-else>
                <ProfileAvatar
                    :name="profile.name"
                    :photo-url="profile.profile_photo_url"
                    size="xl"
                />
                <p class="small text-uppercase text-secondary mt-3 mb-0">
                    Perfil público
                </p>
                <h1 class="display-6 fw-bold mt-2 mb-1">
                    {{ profile.name }}
                </h1>
                <p class="text-secondary mb-0">@{{ profile.username }}</p>
                <p class="small text-secondary mt-3 mb-0">
                    Membro desde {{ profile.member_since }}
                </p>

                <div
                    v-if="flashStatus === 'barbershop-signup-success'"
                    class="alert alert-success mt-3 mb-0"
                    role="alert"
                >
                    Você está cadastrado nesta barbearia.
                </div>

                <BarbershopSignUpButton
                    class="mt-4"
                    :profile="profile"
                    :is-owner="isOwner"
                    :is-authenticated="isAuthenticated"
                    :has-signed-up="hasSignedUp"
                    :requires-payment="requiresPayment"
                    :mercadopago-configured="mercadopagoConfigured"
                />

                <div
                    v-if="hasActiveSubscription && activeSubscription"
                    class="alert alert-success mt-3 mb-0"
                    role="alert"
                >
                    <p class="fw-medium mb-2 mb-sm-0">
                        Você tem uma assinatura ativa.
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                        <CancelSubscriptionButton
                            v-if="activeSubscription.is_cancellable"
                            :subscription-id="activeSubscription.id"
                            :creator-name="profile.name"
                            compact
                        />
                        <Link
                            :href="route('subscriptions.index')"
                            class="link-secondary small"
                        >
                            Gerenciar todas as assinaturas
                        </Link>
                    </div>
                </div>

                <div
                    v-if="isOwner && subscriptionPlan?.is_enabled"
                    class="alert alert-info mt-3 mb-0"
                    role="alert"
                >
                    Acesso pago ativado ({{ subscriptionPlan.formatted_price }}/mês).
                    Compartilhe seu link de assinatura:
                    <span class="font-monospace small text-break d-block mt-1">{{
                        subscribeUrl
                    }}</span>
                </div>

                <p class="small text-secondary mt-4 mb-0">
                    Link do perfil:
                    <span class="font-monospace text-body">{{
                        profile.profile_url
                    }}</span>
                </p>

                <ProfileQrCode
                    class="mt-3"
                    style="max-width: 28rem"
                    :url="profile.profile_url"
                    :filename="`${profile.username}-profile`"
                />

                <div v-if="isOwner" class="d-flex flex-wrap gap-2 mt-4">
                    <Link
                        :href="route('profile.edit')"
                        class="btn btn-primary btn-sm"
                    >
                        Editar perfil
                    </Link>
                    <Link
                        :href="route('dashboard')"
                        class="btn btn-outline-secondary btn-sm"
                    >
                        Painel
                    </Link>
                </div>
            </div>
        </div>
    </component>
</template>
