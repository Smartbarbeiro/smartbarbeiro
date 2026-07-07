<script setup>
import DashboardAlert from '@/Components/DashboardAlert.vue';
import InputError from '@/Components/InputError.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ServicePlanIcon from '@/Components/ServicePlanIcon.vue';
import StepSignupForm from '@/Components/StepSignupForm.vue';
import { barbershopDisplayName } from '@/utils/barbershopDisplayName';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const props = defineProps({
    servicePlans: {
        type: Object,
        required: true,
    },
    barbershopUsername: {
        type: String,
        required: true,
    },
    barbershopName: {
        type: String,
        required: true,
    },
    barbershopPhotoUrl: {
        type: String,
        default: null,
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
    stripeConfigured: {
        type: Boolean,
        default: false,
    },
    hasActiveServicePlanSubscription: {
        type: Boolean,
        default: false,
    },
    pendingServicePlanSubscription: {
        type: Object,
        default: null,
    },
    hasSignedUp: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['checkout-step-change']);

const selectedPackageType = ref(props.servicePlans.packages[0]?.type ?? null);
const selectedAddonIds = ref([]);
const showAddons = ref(false);
const checkoutStep = ref(null);
const paymentMethod = ref(null);

const pixImageUrl = '/images/pix.svg';
const cardImageUrl = '/images/cartao.svg';

const checkoutForm = useForm({
    package_type: selectedPackageType.value,
    addon_ids: [],
});

const guestRegisterForm = useForm({
    name: '',
    cpf: '',
    email: '',
    password: '',
    password_confirmation: '',
    package_type: selectedPackageType.value,
    addon_ids: [],
});

const guestSignupSteps = [
    {
        key: 'name',
        type: 'text',
        placeholder: 'DIGITE SEU NOME AQUI',
        icon: 'bi bi-person',
        autocomplete: 'name',
        required: true,
        emptyMessage: 'Informe seu nome para continuar.',
    },
    {
        key: 'cpf',
        type: 'text',
        placeholder: 'DIGITE SEU CPF AQUI',
        icon: 'bi bi-card-text',
        autocomplete: 'off',
        inputmode: 'numeric',
        required: true,
        emptyMessage: 'Informe seu CPF para continuar.',
    },
    {
        key: 'email',
        type: 'email',
        placeholder: 'DIGITE SEU E-MAIL AQUI',
        icon: 'bi bi-envelope',
        autocomplete: 'email',
        required: true,
        emptyMessage: 'Informe seu e-mail para continuar.',
    },
    {
        key: 'password',
        type: 'password',
        placeholder: 'DIGITE SUA SENHA AQUI',
        icon: 'bi bi-lock',
        autocomplete: 'new-password',
        required: true,
        emptyMessage: 'Informe sua senha para continuar.',
    },
    {
        key: 'password_confirmation',
        type: 'password',
        placeholder: 'CONFIRME SUA SENHA AQUI',
        icon: 'bi bi-shield-lock',
        autocomplete: 'new-password',
        required: true,
        emptyMessage: 'Confirme sua senha para continuar.',
    },
];

const loginUrl = computed(() =>
    route('login', {
        redirect: `/barbearias/${props.barbershopUsername}`,
    }),
);

const barbershopNameForDisplay = computed(() =>
    barbershopDisplayName(props.barbershopUsername, props.barbershopName),
);

const selectedPackage = computed(() =>
    props.servicePlans.packages.find(
        (pkg) => pkg.type === selectedPackageType.value,
    ),
);

const availableAddons = computed(() => props.servicePlans.addons ?? []);

const selectedAddons = computed(() =>
    availableAddons.value.filter((addon) =>
        selectedAddonIds.value.includes(addon.id),
    ),
);

const totalPrice = computed(() => {
    const base = selectedPackage.value?.monthly_price ?? 0;
    const addons = selectedAddons.value.reduce(
        (sum, addon) => sum + addon.monthly_price,
        0,
    );

    return base + addons;
});

const formattedTotal = computed(() =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(totalPrice.value),
);

const toggleAddon = (addonId) => {
    if (selectedAddonIds.value.includes(addonId)) {
        selectedAddonIds.value = selectedAddonIds.value.filter(
            (id) => id !== addonId,
        );
    } else {
        selectedAddonIds.value = [...selectedAddonIds.value, addonId];
    }
};

const buildPlan = () => {
    if (!selectedPackage.value) {
        return;
    }

    paymentMethod.value = null;
    checkoutStep.value = 'summary';
};

const openPendingCheckout = () => {
    if (!props.pendingServicePlanSubscription) {
        return;
    }

    selectedPackageType.value =
        props.pendingServicePlanSubscription.package_type;
    selectedAddonIds.value = [
        ...(props.pendingServicePlanSubscription.selected_addon_ids ?? []),
    ];
    paymentMethod.value = null;
    checkoutStep.value = 'payment';
};

const restorePendingCheckout = () => {
    if (
        props.isOwner ||
        !props.isAuthenticated ||
        props.hasActiveServicePlanSubscription ||
        !props.pendingServicePlanSubscription
    ) {
        return;
    }

    openPendingCheckout();
};

const goToPayment = () => {
    checkoutStep.value = 'payment';
};

const backToSummary = () => {
    paymentMethod.value = null;
    checkoutForm.clearErrors();
    guestRegisterForm.clearErrors();
    checkoutStep.value = 'summary';
};

const confirmCheckoutPayment = () => {
    if (props.pendingServicePlanSubscription) {
        confirmPendingPayment();

        return;
    }

    confirmSubscription();
};

const confirmSubscription = (packageType = selectedPackageType.value, addonIds = selectedAddonIds.value) => {
    if (props.isOwner || !props.isAuthenticated) {
        return;
    }

    if (!props.stripeConfigured) {
        return;
    }

    checkoutForm.package_type = packageType;
    checkoutForm.addon_ids = [...addonIds];

    checkoutForm.post(
        route('service-plan.subscribe', {
            username: props.barbershopUsername,
        }),
        {
            preserveScroll: true,
        },
    );
};

const confirmPendingPayment = () => {
    if (!props.pendingServicePlanSubscription || props.isOwner || !props.isAuthenticated) {
        return;
    }

    confirmSubscription(
        props.pendingServicePlanSubscription.package_type,
        props.pendingServicePlanSubscription.selected_addon_ids ?? [],
    );
};

const planBuilderSubmitLabel = computed(() => {
    if (
        props.pendingServicePlanSubscription &&
        props.isAuthenticated &&
        !props.isOwner
    ) {
        return 'CONTINUAR PAGAMENTO';
    }

    return props.hasSignedUp ? 'ALTERE SEU PLANO' : 'MONTE SEU PLANO';
});

const handlePlanSubmit = () => {
    if (
        props.pendingServicePlanSubscription &&
        props.isAuthenticated &&
        !props.isOwner
    ) {
        openPendingCheckout();

        return;
    }

    buildPlan();
};

const isGuest = computed(() => !props.isAuthenticated && !props.isOwner);

const checkoutError = computed(
    () =>
        checkoutForm.errors.checkout ??
        page.props.errors?.checkout ??
        guestRegisterForm.errors.checkout ??
        null,
);

const editPlan = () => {
    checkoutStep.value = null;
    paymentMethod.value = null;
    checkoutForm.clearErrors();
    guestRegisterForm.clearErrors();
};

const scrollToCheckoutHash = (hash) => {
    const targetId = hash === '#pagamento' ? 'pagamento' : 'plano';

    document
        .getElementById(targetId)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const syncCheckoutHash = (step) => {
    const hash = step === 'payment' ? '#pagamento' : step ? '#plano' : '';
    const url = `${window.location.pathname}${window.location.search}${hash}`;

    if (window.location.hash !== hash) {
        window.history.replaceState(null, '', url);
    }

    if (hash) {
        scrollToCheckoutHash(hash);
    }
};

const handleCheckoutHashChange = () => {
    if (!checkoutStep.value) {
        return;
    }

    if (window.location.hash === '#pagamento' && checkoutStep.value === 'summary') {
        checkoutStep.value = 'payment';
        return;
    }

    if (
        window.location.hash === '#plano' &&
        checkoutStep.value === 'payment'
    ) {
        backToSummary();
    }
};

const submitGuestRegistration = () => {
    guestRegisterForm.package_type = selectedPackageType.value;
    guestRegisterForm.addon_ids = [...selectedAddonIds.value];

    guestRegisterForm.post(
        route('service-plan.subscribe.register', {
            username: props.barbershopUsername,
        }),
        {
            preserveScroll: true,
            onFinish: () =>
                guestRegisterForm.reset('password', 'password_confirmation'),
        },
    );
};

watch(checkoutStep, (step) => {
    emit('checkout-step-change', step);
    syncCheckoutHash(step);
});

onMounted(() => {
    window.addEventListener('hashchange', handleCheckoutHashChange);
    restorePendingCheckout();
});

watch(
    () => props.pendingServicePlanSubscription,
    () => {
        restorePendingCheckout();
    },
);

onUnmounted(() => {
    window.removeEventListener('hashchange', handleCheckoutHashChange);
});
</script>

<template>
    <section v-if="servicePlans.packages.length > 0" class="plan-builder">
        <DashboardAlert
            v-if="isAuthenticated"
            :show="hasActiveServicePlanSubscription"
            variant="success"
        >
            Você já tem um plano de serviço ativo nesta barbearia.
        </DashboardAlert>
        <div
            v-else-if="hasActiveServicePlanSubscription"
            class="alert alert-success mb-3"
            role="alert"
        >
            Você já tem um plano de serviço ativo nesta barbearia.
        </div>

        <DashboardAlert
            v-if="isAuthenticated"
            :show="isOwner"
            variant="info"
        >
            <p class="mb-0">
                Esta é a visualização do montador de planos para seus clientes.
            </p>
        </DashboardAlert>
        <div
            v-else-if="isOwner"
            class="alert alert-info mb-3 text-start"
            role="alert"
        >
            <p class="mb-0">
                Esta é a visualização do montador de planos para seus clientes.
            </p>
        </div>

        <form class="plan-form" @submit.prevent="handlePlanSubmit">
            <template v-if="!checkoutStep || isOwner">
                <fieldset>
                    <legend class="visually-hidden">Escolha seu plano mensal</legend>
                    <div class="row g-3 justify-content-center">
                    <div
                        v-for="pkg in servicePlans.packages"
                        :key="pkg.type"
                        class="col-6"
                    >
                        <input
                            :id="`package-${pkg.type}`"
                            v-model="selectedPackageType"
                            class="radioBtn"
                            name="servico"
                            type="radio"
                            :value="pkg.type"
                        />
                        <label :for="`package-${pkg.type}`" class="plan-card">
                            <div class="plan-card-body">
                                <ServicePlanIcon
                                    :variant="
                                        pkg.type === 'cut' ? 'cut' : 'cut_beard'
                                    "
                                />
                                <p v-if="pkg.type === 'cut'">
                                    <strong>CORTE<br />CABELO</strong>
                                </p>
                                <p v-else>
                                    <strong
                                        >CORTE CABELO<br /><span
                                            class="text-decoration-underline"
                                            >+ BARBA</span
                                        ></strong
                                    >
                                </p>
                                <p class="plan-card-price small mb-0">
                                    {{ pkg.formatted_price }}/mês
                                </p>
                            </div>
                            <div class="plan-card-footer">ILIMITADOS</div>
                        </label>
                    </div>
                    </div>
                </fieldset>

                <button
                    v-if="availableAddons.length > 0 && !showAddons"
                    type="button"
                    class="btn-optionals og-btn og-btn--secondary"
                    @click="showAddons = true"
                >
                    <span class="og-btn__label">
                        <span class="btn-optionals-icon" aria-hidden="true">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                        ADICIONAR OPCIONAIS
                    </span>
                </button>

                <hr
                    v-if="showAddons && availableAddons.length > 0"
                    class="plan-addons-divider"
                />

                <div
                    v-if="showAddons && availableAddons.length > 0"
                    class="plan-addons-panel text-start"
                >
                    <p class="fw-semibold mb-3">Opcionais disponíveis</p>
                    <div
                        v-for="addon in availableAddons"
                        :key="addon.id"
                        class="form-check mb-2"
                    >
                        <input
                            :id="`addon-${addon.id}`"
                            class="form-check-input"
                            type="checkbox"
                            :checked="selectedAddonIds.includes(addon.id)"
                            @change="toggleAddon(addon.id)"
                        />
                        <label class="form-check-label" :for="`addon-${addon.id}`">
                            {{ addon.name }}
                            <span class="text-secondary"
                                >({{ addon.formatted_price }}/mês)</span
                            >
                        </label>
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-plan-submit og-btn og-btn--primary"
                    :disabled="hasActiveServicePlanSubscription"
                >
                    <span class="og-btn__label">{{ planBuilderSubmitLabel }}</span>
                </button>

                <p v-if="isGuest && !checkoutStep" class="plan-guest-login mt-3 mb-0">
                    Já tem uma conta,
                    <Link :href="loginUrl">entre aqui</Link>
                </p>
            </template>

            <div
                v-if="checkoutStep === 'summary' && selectedPackage"
                :class="
                    isOwner
                        ? 'alert alert-info mb-3 mt-3 text-start'
                        : 'plan-checkout text-start'
                "
                :role="isOwner ? 'alert' : undefined"
            >
                <div :class="{ 'plan-checkout text-start': isOwner }">
                    <div class="section-header mb-4">
                        <button
                            type="button"
                            class="back-button border-0 og-btn og-btn--neutral"
                            :disabled="checkoutForm.processing"
                            @click="editPlan"
                        >
                            <span class="og-btn__label">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                Voltar
                            </span>
                        </button>
                    </div>

                    <div
                        class="row justify-content-center text-center text-md-start"
                    >
                        <div
                            class="col-md-8 service-item perfil-barbearia justify-content-center justify-content-md-start"
                        >
                            <ProfileAvatar
                                class="service-logo"
                                :name="barbershopNameForDisplay"
                                :photo-url="barbershopPhotoUrl"
                                size="lg"
                            />
                            <div class="perfil-info">
                                <h3 class="barbershop-profile-name mb-0">
                                    {{ barbershopNameForDisplay }}
                                </h3>
                            </div>
                        </div>
                    </div>

                    <div class="carrinho-items mt-4">
                        <h3 class="h6 fw-bold mb-3">Resumo do plano</h3>
                        <ul class="list-unstyled mb-0">
                            <li>{{ selectedPackage.label }} — ilimitados</li>
                            <li
                                v-for="addon in selectedAddons"
                                :key="addon.id"
                                class="text-secondary"
                            >
                                {{ addon.name }} — {{ addon.formatted_price }}/mês
                            </li>
                            <li
                                v-if="selectedAddons.length === 0"
                                class="text-secondary"
                            >
                                Nenhum opcional selecionado
                            </li>
                        </ul>
                    </div>

                    <div class="carrinho-total">
                        <p class="h5 fw-bold mb-0">
                            Total: {{ formattedTotal }}/mês
                        </p>
                    </div>

                    <div class="row g-3 justify-content-center mt-4">
                        <div class="col-12 col-md-6">
                            <button
                                type="button"
                                class="btn btn-plan-submit og-btn og-btn--primary w-100"
                                @click="goToPayment"
                            >
                                <span class="og-btn__label">
                                    CONTINUAR PARA PAGAMENTO
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="checkoutStep === 'payment' && selectedPackage"
                id="pagamento"
                :class="
                    isOwner
                        ? 'alert alert-info mb-3 mt-3 text-start'
                        : 'plan-checkout plan-payment text-start'
                "
                :role="isOwner ? 'alert' : undefined"
            >
                <div :class="{ 'plan-checkout text-start': isOwner }">
                    <div class="section-header mb-4">
                        <button
                            type="button"
                            class="back-button border-0 og-btn og-btn--neutral"
                            :disabled="
                                checkoutForm.processing ||
                                guestRegisterForm.processing
                            "
                            @click="backToSummary"
                        >
                            <span class="og-btn__label">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                Voltar
                            </span>
                        </button>
                    </div>

                    <div class="carrinho-total mb-4">
                        <p class="h6 fw-bold mb-1">Total do plano</p>
                        <p class="h5 fw-bold mb-0">
                            {{ formattedTotal }}/mês
                        </p>
                    </div>

                    <p class="h6 fw-bold mb-3">
                        Método de Pagamento:
                    </p>

                    <fieldset>
                        <legend class="visually-hidden">
                            Método de pagamento
                        </legend>

                        <div class="row g-3 justify-content-center">
                            <div class="col-12 col-md-6">
                                <button
                                    type="button"
                                    class="card-pagamento w-100"
                                    :class="{
                                        'card-pagamento--selected':
                                            paymentMethod === 'pix',
                                    }"
                                    @click="paymentMethod = 'pix'"
                                >
                                    <h3 class="h6 fw-bold mb-3">
                                        Pix Recorrente
                                    </h3>
                                    <img
                                        :src="pixImageUrl"
                                        alt="Pix"
                                        class="service-pix img-fluid"
                                    />
                                </button>
                            </div>
                        </div>

                        <div
                            class="row g-3 justify-content-center plan-payment-row-spaced"
                        >
                            <div class="col-12 col-md-6">
                                <button
                                    type="button"
                                    class="card-pagamento w-100"
                                    :class="{
                                        'card-pagamento--selected':
                                            paymentMethod === 'card',
                                    }"
                                    @click="paymentMethod = 'card'"
                                >
                                    <h3 class="h6 fw-bold mb-3">
                                        Cartão de Crédito e Débito
                                    </h3>
                                    <img
                                        :src="cardImageUrl"
                                        alt="Cartão"
                                        class="service-pix img-fluid"
                                    />
                                </button>
                            </div>
                        </div>
                    </fieldset>

                    <InputError class="mt-3 mb-0" :message="checkoutError" />

                    <p
                        v-if="!isOwner && !stripeConfigured && (isGuest || isAuthenticated)"
                        class="small text-warning mt-4 mb-0"
                    >
                        Pagamentos indisponíveis no momento. Volte aqui para
                        confirmar o pagamento quando estiverem disponíveis.
                    </p>

                    <div v-if="isGuest" class="mt-4">
                        <p class="h6 fw-bold mb-3">
                            Crie sua conta para assinar
                        </p>

                        <StepSignupForm
                            :form="guestRegisterForm"
                            :steps="guestSignupSteps"
                            :processing="guestRegisterForm.processing"
                            plain
                            hide-header
                            @submit="submitGuestRegistration"
                        />

                        <InputError class="mt-3 mb-0" :message="checkoutError" />

                        <p class="plan-guest-login mt-3 mb-0">
                            Já tem uma conta,
                            <Link :href="loginUrl">entre aqui</Link>
                        </p>
                    </div>

                    <div
                        v-else-if="isAuthenticated || isOwner"
                        class="row g-3 justify-content-center mt-4"
                    >
                        <div
                            v-if="
                                isAuthenticated &&
                                stripeConfigured &&
                                !isOwner &&
                                paymentMethod
                            "
                            class="col-12 col-md-6"
                        >
                            <button
                                type="button"
                                class="btn btn-plan-submit og-btn og-btn--primary w-100"
                                :disabled="
                                    checkoutForm.processing ||
                                    hasActiveServicePlanSubscription
                                "
                                @click="confirmCheckoutPayment"
                            >
                                <span class="og-btn__label">
                                    {{
                                        checkoutForm.processing
                                            ? 'REDIRECIONANDO...'
                                            : 'CONFIRMAR PAGAMENTO'
                                    }}
                                </span>
                            </button>
                        </div>

                        <div
                            v-else-if="isOwner && paymentMethod"
                            class="col-12 col-md-6"
                        >
                            <button
                                type="button"
                                class="btn btn-plan-submit og-btn og-btn--primary w-100"
                                disabled
                            >
                                <span class="og-btn__label">CONFIRMAR PAGAMENTO</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
