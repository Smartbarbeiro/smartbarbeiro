<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarbershopPublicLayout from '@/Layouts/BarbershopPublicLayout.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
import PlanBuilder from '@/Components/PlanBuilder.vue';
import PreferredHaircutDayPicker from '@/Components/PreferredHaircutDayPicker.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    needsPreferredHaircutDay: {
        type: Boolean,
        default: false,
    },
    preferredHaircutDay: {
        type: Number,
        default: null,
    },
    servicePlans: {
        type: Object,
        default: () => ({ packages: [], addons: [] }),
    },
    hasActiveServicePlanSubscription: {
        type: Boolean,
        default: false,
    },
    activeServicePlanSubscription: {
        type: Object,
        default: null,
    },
    pendingServicePlanSubscription: {
        type: Object,
        default: null,
    },
    acrylicQrOrder: {
        type: Object,
        default: null,
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

const profileSubscriptionPaymentOnTime = computed(
    () =>
        !props.isOwner &&
        props.activeSubscription?.is_active === true,
);

const profileSubscriptionNeedsPaymentUpdate = computed(() => {
    if (props.isOwner || !props.activeSubscription) {
        return false;
    }

    return ['pending', 'paused'].includes(props.activeSubscription.status);
});

const showProfileSubscribePaywall = computed(
    () =>
        showPaywall.value &&
        !profileSubscriptionNeedsPaymentUpdate.value &&
        !profileSubscriptionPaymentOnTime.value,
);

const layoutComponent = computed(() =>
    isAuthenticated.value ? AuthenticatedLayout : BarbershopPublicLayout,
);

const planCheckoutActive = ref(false);
</script>

<template>
    <component :is="layoutComponent">
        <Head :title="isOwner && isAuthenticated ? 'Sua Barbearia' : profile.name" />

        <template v-if="isAuthenticated" #header>
            <h1 class="h4 mb-0 fw-semibold">
                {{ isOwner ? 'Sua Barbearia' : profile.name }}
            </h1>
        </template>

        <div
            class="barbershop-profile-page"
            :class="{
                'barbershop-profile-page--authenticated': isAuthenticated,
            }"
        >
            <section
                v-show="!planCheckoutActive"
                class="barbershop-profile-services"
            >
                <div class="container text-center">
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6 service-item">
                            <ProfileAvatar
                                class="service-logo mx-auto d-block"
                                :name="profile.name"
                                :photo-url="profile.profile_photo_url"
                                size="xl"
                            />

                            <h2 class="barbershop-profile-name mt-3 mb-1">
                                {{ profile.name }}
                            </h2>

                            <p class="barbershop-profile-meta mb-0">
                                @{{ profile.username }}
                            </p>

                            <p
                                v-if="!showPaywall"
                                class="barbershop-profile-meta small mt-2 mb-0"
                            >
                                Membro desde {{ profile.member_since }}
                            </p>

                            <p
                                v-if="showProfileSubscribePaywall && subscriptionPlan?.description"
                                class="barbershop-profile-meta mt-3 mb-0 mx-auto"
                                style="max-width: 28rem"
                            >
                                {{ subscriptionPlan.description }}
                            </p>

                            <p
                                v-if="showProfileSubscribePaywall"
                                class="plan-card-price display-6 fw-semibold mt-3 mb-0"
                            >
                                {{ subscriptionPlan.formatted_price }}
                                <span class="fs-6 fw-normal">/ mês</span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="flashStatus === 'preferred-haircut-day-saved'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Preferência salva. Seu dia preferido para o corte é dia
                    {{ preferredHaircutDay }}.
                </div>
            </section>

            <section
                v-else-if="
                    preferredHaircutDay &&
                    hasSignedUp &&
                    !isOwner &&
                    isAuthenticated
                "
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-info mb-0" role="alert">
                    Seu dia preferido para o corte: dia {{ preferredHaircutDay }}.
                </div>
            </section>

            <section
                v-if="flashStatus === 'barbershop-signup-success'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Você está cadastrado nesta barbearia.
                </div>
            </section>

            <section
                v-if="flashStatus === 'service-plan-signup-pending'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Cadastro realizado. Confirme o pagamento do plano quando os
                    pagamentos estiverem disponíveis.
                </div>
            </section>

            <section
                v-if="servicePlans.packages.length > 0"
                class="plan-builder"
            >
                <div
                    class="container"
                    :class="planCheckoutActive ? 'text-start' : 'text-center'"
                >
                    <PlanBuilder
                        :service-plans="servicePlans"
                        :barbershop-username="profile.username"
                        :barbershop-name="profile.name"
                        :barbershop-photo-url="profile.profile_photo_url"
                        :is-authenticated="isAuthenticated"
                        :is-owner="isOwner"
                        :mercadopago-configured="mercadopagoConfigured"
                        :has-active-service-plan-subscription="hasActiveServicePlanSubscription"
                        :pending-service-plan-subscription="pendingServicePlanSubscription"
                        :has-signed-up="hasSignedUp"
                        @checkout-step-change="planCheckoutActive = $event"
                    >
                        <template
                            v-if="isOwner && !showPaywall"
                            #owner-below-plan
                        >
                            <p class="small text-secondary mb-2">
                                Link do perfil:
                                <span class="font-monospace text-body">{{
                                    profile.profile_url
                                }}</span>
                            </p>

                            <ProfileQrCode
                                class="mx-auto"
                                style="max-width: 28rem"
                                :url="profile.profile_url"
                                :filename="`${profile.username}-profile`"
                                show-acrylic-order
                                :acrylic-order="acrylicQrOrder"
                            />

                            <div
                                class="d-flex flex-wrap justify-content-center gap-2 mt-4"
                            >
                                <Link
                                    :href="`${route('profile.edit')}#planos-de-servico`"
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
                        </template>
                    </PlanBuilder>
                </div>
            </section>

            <section
                v-else-if="isOwner"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-warning mb-0" role="alert">
                    Configure os preços dos seus planos em Editar perfil para
                    exibir o montador de planos aos clientes.
                </div>
            </section>

            <section
                v-if="hasActiveServicePlanSubscription && activeServicePlanSubscription"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Plano ativo: {{ activeServicePlanSubscription.package_label }}
                    ({{ activeServicePlanSubscription.formatted_total }}/mês)
                </div>
            </section>

            <section
                v-if="profileSubscriptionPaymentOnTime && activeSubscription"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    <p class="fw-medium mb-2 mb-sm-0">Pagamento em dia.</p>
                    <p
                        v-if="activeSubscription.next_payment_date"
                        class="small mb-2 mb-sm-0"
                    >
                        Próximo pagamento:
                        {{
                            new Date(
                                activeSubscription.next_payment_date,
                            ).toLocaleDateString('pt-BR')
                        }}
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
                            Gerenciar assinaturas
                        </Link>
                    </div>
                </div>
            </section>

            <section
                v-if="profileSubscriptionNeedsPaymentUpdate && activeSubscription"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-warning mb-0" role="alert">
                    <p class="fw-medium mb-2">
                        Atualize a forma de pagamento para manter seu acesso.
                    </p>
                    <p class="small mb-3">
                        Status:
                        <span class="badge bg-secondary">{{
                            activeSubscription.status_label
                        }}</span>
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <Link
                            v-if="mercadopagoConfigured"
                            :href="
                                route('profile.subscribe', {
                                    username: profile.username,
                                })
                            "
                            method="post"
                            as="button"
                            class="btn btn-primary btn-sm"
                        >
                            Atualizar forma de pagamento
                        </Link>
                        <Link
                            :href="route('subscriptions.index')"
                            class="link-secondary small"
                        >
                            Gerenciar assinaturas
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <PreferredHaircutDayPicker
            :show="needsPreferredHaircutDay && isAuthenticated && !isOwner"
            :barbershop-username="profile.username"
            :preferred-haircut-day="preferredHaircutDay"
        />
    </component>
</template>
