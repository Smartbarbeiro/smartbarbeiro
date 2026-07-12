<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarbershopPublicLayout from '@/Layouts/BarbershopPublicLayout.vue';
import BookAppointmentCard from '@/Components/BookAppointmentCard.vue';
import CancelSubscriptionButton from '@/Components/CancelSubscriptionButton.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import MobileAppPromo from '@/Components/MobileAppPromo.vue';
import PaymentEmailMismatchCard from '@/Components/PaymentEmailMismatchCard.vue';
import PlanBuilder from '@/Components/PlanBuilder.vue';
import PreferredHaircutDayPicker from '@/Components/PreferredHaircutDayPicker.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { barbershopDisplayName } from '@/utils/barbershopDisplayName';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

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
    stripeConfigured: {
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
    mobileApp: {
        type: Object,
        default: () => ({
            name: 'Smart Barbeiro',
            play_store_url: null,
            app_store_url: null,
        }),
    },
    canBookAppointment: {
        type: Boolean,
        default: false,
    },
    defaultAppointmentService: {
        type: Object,
        default: () => ({
            label: 'Serviço',
            package_type: null,
        }),
    },
    paymentEmailMismatch: {
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

const showMobileAppPromo = computed(
    () =>
        props.servicePlans.packages.length > 0 &&
        !props.isOwner &&
        Boolean(
            props.mobileApp?.play_store_url ||
                props.mobileApp?.app_store_url,
        ),
);

const planCheckoutStep = ref(null);
const planCheckoutActive = computed(() => Boolean(planCheckoutStep.value));
const ownerQrExpanded = ref(false);
const ownerQrPanel = ref(null);
const preferredDayPickerOpen = ref(false);

const toggleOwnerQr = async () => {
    ownerQrExpanded.value = !ownerQrExpanded.value;

    if (!ownerQrExpanded.value) {
        return;
    }

    await nextTick();

    const el = ownerQrPanel.value;

    if (!el) {
        return;
    }

    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.focus({ preventScroll: true });
};

const showPreferredDayPicker = computed(
    () =>
        (props.needsPreferredHaircutDay ||
            preferredDayPickerOpen.value) &&
        isAuthenticated.value &&
        !props.isOwner,
);

const preferredDayPickerCloseable = computed(
    () => !props.needsPreferredHaircutDay,
);

const showPreferredDayNotice = computed(
    () =>
        flashStatus.value !== 'preferred-haircut-day-saved' &&
        props.preferredHaircutDay &&
        props.hasSignedUp &&
        !props.isOwner &&
        isAuthenticated.value,
);

const hasPlanBuilder = computed(() => props.servicePlans.packages.length > 0);

const hideAppTopbar = computed(
    () =>
        isAuthenticated.value &&
        !props.isOwner &&
        hasPlanBuilder.value,
);

const layoutProps = computed(() =>
    isAuthenticated.value ? { hideTopbar: hideAppTopbar.value } : {},
);

const barbershopNameForDisplay = computed(() =>
    barbershopDisplayName(props.profile.username, props.profile.name),
);

const scrollToCheckoutSection = () => {
    const hash = window.location.hash;

    if (hash !== '#plano' && hash !== '#pagamento') {
        return;
    }

    const targetId = hash === '#pagamento' ? 'pagamento' : 'plano';

    document
        .getElementById(targetId)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

onMounted(() => {
    scrollToCheckoutSection();
    window.addEventListener('hashchange', scrollToCheckoutSection);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', scrollToCheckoutSection);
});
</script>

<template>
    <component :is="layoutComponent" v-bind="layoutProps">
        <Head :title="isOwner && isAuthenticated ? 'Sua Barbearia' : profile.name" />

        <template v-if="isAuthenticated && !hideAppTopbar" #header>
            <DashboardPageHeader
                :icon="isOwner ? 'barbershop' : 'profile'"
                :title="isOwner ? 'Sua Barbearia' : profile.name"
            />
        </template>

        <div
            class="barbershop-profile-page"
            :class="{
                'barbershop-profile-page--authenticated': isAuthenticated,
            }"
        >
            <DashboardAlert
                v-if="isAuthenticated"
                :show="flashStatus === 'preferred-haircut-day-saved'"
                variant="success"
            >
                Preferência salva. Seu dia preferido para o corte é dia
                {{ preferredHaircutDay }}.
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="showPreferredDayNotice"
                variant="info"
            >
                <div
                    class="d-flex flex-wrap align-items-center justify-content-between gap-3"
                >
                    <p class="mb-0">
                        Seu dia preferido para o corte: dia
                        {{ preferredHaircutDay }}.
                    </p>

                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="preferredDayPickerOpen = true"
                    >
                        Atualizar dia preferido
                    </button>
                </div>
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="flashStatus === 'appointment-requested'"
                variant="success"
            >
                Solicitação enviada. A barbearia vai confirmar seu horário na agenda.
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="flashStatus === 'barbershop-signup-success'"
                variant="success"
            >
                Você está cadastrado nesta barbearia.
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="flashStatus === 'service-plan-signup-pending'"
                variant="success"
            >
                Cadastro realizado. Confirme o pagamento do plano quando os
                pagamentos estiverem disponíveis.
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="isOwner && servicePlans.packages.length === 0"
                variant="warning"
            >
                Configure os preços dos seus planos em Editar perfil para
                exibir o montador de planos aos clientes.
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="
                    hasActiveServicePlanSubscription &&
                    Boolean(activeServicePlanSubscription)
                "
                variant="success"
            >
                Plano ativo: {{ activeServicePlanSubscription.package_label }}
                ({{ activeServicePlanSubscription.formatted_total }}/mês)
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="Boolean(paymentEmailMismatch) && !isOwner"
                variant="warning"
            >
                <PaymentEmailMismatchCard
                    v-if="paymentEmailMismatch"
                    :mismatch="paymentEmailMismatch"
                    compact
                />
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="
                    profileSubscriptionPaymentOnTime && Boolean(activeSubscription)
                "
                variant="success"
            >
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
            </DashboardAlert>

            <DashboardAlert
                v-if="isAuthenticated"
                :show="
                    profileSubscriptionNeedsPaymentUpdate &&
                    Boolean(activeSubscription)
                "
                variant="warning"
            >
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
            </DashboardAlert>

            <section
                v-if="canBookAppointment && isAuthenticated && !isOwner"
                class="container barbershop-profile-notices text-center"
            >
                <BookAppointmentCard
                    :username="profile.username"
                    :default-service-label="defaultAppointmentService.label"
                    :default-package-type="defaultAppointmentService.package_type"
                />
            </section>

            <section
                v-show="!planCheckoutActive"
                class="barbershop-profile-services"
            >
                <div class="container text-center">
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6 service-item">
                            <ProfileAvatar
                                class="service-logo mx-auto d-block"
                                :name="
                                    hasPlanBuilder
                                        ? barbershopNameForDisplay
                                        : profile.name
                                "
                                :photo-url="profile.profile_photo_url"
                                size="xl"
                            />

                            <h2 class="barbershop-profile-name mt-3 mb-1">
                                {{
                                    hasPlanBuilder
                                        ? barbershopNameForDisplay
                                        : profile.name
                                }}
                            </h2>

                            <p
                                v-if="!hasPlanBuilder"
                                class="barbershop-profile-meta mb-0"
                            >
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
                v-if="!isAuthenticated && flashStatus === 'preferred-haircut-day-saved'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Preferência salva. Seu dia preferido para o corte é dia
                    {{ preferredHaircutDay }}.
                </div>
            </section>

            <section
                v-if="
                    !isAuthenticated &&
                    preferredHaircutDay &&
                    hasSignedUp &&
                    !isOwner
                "
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-info mb-0" role="alert">
                    <div
                        class="d-flex flex-wrap align-items-center justify-content-between gap-3"
                    >
                        <p class="mb-0">
                            Seu dia preferido para o corte: dia
                            {{ preferredHaircutDay }}.
                        </p>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="preferredDayPickerOpen = true"
                        >
                            Atualizar dia preferido
                        </button>
                    </div>
                </div>
            </section>

            <section
                v-if="!isAuthenticated && flashStatus === 'barbershop-signup-success'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Você está cadastrado nesta barbearia.
                </div>
            </section>

            <section
                v-if="!isAuthenticated && flashStatus === 'service-plan-signup-pending'"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Cadastro realizado. Confirme o pagamento do plano quando os
                    pagamentos estiverem disponíveis.
                </div>
            </section>

            <MobileAppPromo
                v-if="showMobileAppPromo"
                :mobile-app="mobileApp"
                :with-topbar-offset="isAuthenticated"
            />

            <section
                v-if="servicePlans.packages.length > 0"
                id="plano"
                class="plan-builder"
                :class="{
                    'plan-builder--mobile-app-promo': showMobileAppPromo,
                    'plan-builder--in-app': isAuthenticated,
                }"
            >
                <div
                    class="container"
                    :class="planCheckoutActive ? 'text-start' : 'text-center'"
                >
                    <div
                        v-if="isOwner && !showPaywall"
                        class="d-flex flex-wrap justify-content-center gap-2 mb-3"
                    >
                        <Link
                            :href="route('services.index')"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-pencil-square me-2"></i>
                            Editar meus planos
                        </Link>

                        <button
                            type="button"
                            class="btn-share-qrcode"
                            :aria-expanded="ownerQrExpanded"
                            @click="toggleOwnerQr"
                        >
                            <i class="bi bi-qr-code me-2" aria-hidden="true"></i>
                            Compartilhar Qr-code
                        </button>
                    </div>

                    <div
                        v-if="isOwner && !showPaywall && ownerQrExpanded"
                        ref="ownerQrPanel"
                        tabindex="-1"
                        class="barbershop-owner-qr-panel mb-4 mx-auto"
                    >
                        <ProfileQrCode
                            v-model:expanded="ownerQrExpanded"
                            :url="profile.profile_url"
                            :filename="`${profile.username}-profile`"
                            hide-share-button
                            owner-dashboard
                            show-acrylic-order
                            :acrylic-order="acrylicQrOrder"
                        />
                    </div>

                    <PlanBuilder
                        :service-plans="servicePlans"
                        :barbershop-username="profile.username"
                        :barbershop-name="barbershopNameForDisplay"
                        :barbershop-photo-url="profile.profile_photo_url"
                        :is-authenticated="isAuthenticated"
                        :is-owner="isOwner"
                        :mercadopago-configured="mercadopagoConfigured"
                        :stripe-configured="stripeConfigured"
                        :has-active-service-plan-subscription="hasActiveServicePlanSubscription"
                        :active-service-plan-subscription="activeServicePlanSubscription"
                        :pending-service-plan-subscription="pendingServicePlanSubscription"
                        :has-signed-up="hasSignedUp"
                        @checkout-step-change="planCheckoutStep = $event"
                    />
                </div>
            </section>

            <section
                v-if="!isAuthenticated && isOwner && servicePlans.packages.length === 0"
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-warning mb-0" role="alert">
                    Configure os preços dos seus planos em Editar perfil para
                    exibir o montador de planos aos clientes.
                </div>
            </section>

            <section
                v-if="
                    !isAuthenticated &&
                    hasActiveServicePlanSubscription &&
                    activeServicePlanSubscription
                "
                class="container barbershop-profile-notices"
            >
                <div class="alert alert-success mb-0" role="alert">
                    Plano ativo: {{ activeServicePlanSubscription.package_label }}
                    ({{ activeServicePlanSubscription.formatted_total }}/mês)
                </div>
            </section>

            <section
                v-if="
                    !isAuthenticated &&
                    profileSubscriptionPaymentOnTime &&
                    activeSubscription
                "
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
                v-if="
                    !isAuthenticated &&
                    profileSubscriptionNeedsPaymentUpdate &&
                    activeSubscription
                "
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
            :show="showPreferredDayPicker"
            :closeable="preferredDayPickerCloseable"
            :barbershop-username="profile.username"
            :preferred-haircut-day="preferredHaircutDay"
            @close="preferredDayPickerOpen = false"
        />
    </component>
</template>
