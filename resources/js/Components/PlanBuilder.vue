<script setup>
import InputError from '@/Components/InputError.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import StepSignupForm from '@/Components/StepSignupForm.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
    mercadopagoConfigured: {
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
const showSummary = ref(false);
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
    showSummary.value = true;
};

const openPendingCheckout = () => {
    if (!props.pendingServicePlanSubscription || !props.mercadopagoConfigured) {
        return;
    }

    selectedPackageType.value =
        props.pendingServicePlanSubscription.package_type;
    selectedAddonIds.value = [
        ...(props.pendingServicePlanSubscription.selected_addon_ids ?? []),
    ];
    paymentMethod.value = null;
    showSummary.value = true;
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

    if (!props.mercadopagoConfigured) {
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
        props.mercadopagoConfigured &&
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
        props.mercadopagoConfigured &&
        props.isAuthenticated &&
        !props.isOwner
    ) {
        openPendingCheckout();

        return;
    }

    buildPlan();
};

const isGuest = computed(() => !props.isAuthenticated && !props.isOwner);

const editPlan = () => {
    showSummary.value = false;
    paymentMethod.value = null;
    checkoutForm.clearErrors();
    guestRegisterForm.clearErrors();
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

watch(showSummary, (active) => {
    emit('checkout-step-change', active);
});
</script>

<template>
    <section v-if="servicePlans.packages.length > 0" class="plan-builder">
        <div
            v-if="hasActiveServicePlanSubscription"
            class="alert alert-success mb-3"
            role="alert"
        >
            Você já tem um plano de serviço ativo nesta barbearia.
        </div>

        <div
            v-if="isOwner"
            class="alert alert-info mb-3 text-start"
            role="alert"
        >
            <p class="mb-0">
                Esta é a visualização do montador de planos para seus clientes.
            </p>
        </div>

        <form class="plan-form" @submit.prevent="handlePlanSubmit">
            <template v-if="!showSummary || isOwner">
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
                                <svg
                                    v-if="pkg.type === 'cut'"
                                    width="97"
                                    height="130"
                                    viewBox="0 0 97 130"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M50.9736 0.129848C46.4357 0.851214 42.9821 1.94787 38.698 4.02761C32.2687 7.14887 27.6589 10.5349 20.1824 17.6276C17.393 20.2742 16.0907 21.3373 15.4074 21.5269C14.3377 21.8234 13.0079 21.6405 13.0079 21.1968C13.0079 21.0329 13.4035 20.2755 13.8872 19.5135C14.4248 18.6663 14.7693 17.8022 14.7731 17.2904C14.7819 16.1465 13.8293 15.2056 12.6622 15.2056C11.9067 15.2056 11.5766 15.4363 10.2448 16.8956C6.76714 20.7054 2.83516 27.5793 1.40056 32.3568C0.144914 36.5384 -0.0223887 37.6782 0.00216239 41.8852C0.0219045 45.3215 0.111758 46.1453 0.706047 48.3462C2.00599 53.1625 4.76306 57.865 8.53786 61.7043L10.4113 63.61L10.5591 71.4869C10.7166 79.8767 11.0712 83.8296 12.154 89.273C14.158 99.3485 18.1702 106.187 25.5946 112.182L27.9411 114.076V121.431C27.9411 128.414 27.9662 128.81 28.441 129.286C29.1099 129.958 30.117 130.183 30.8837 129.833C32.0556 129.296 32.2439 128.271 32.2439 122.426V117.068L33.0039 117.518C33.4221 117.766 35.1589 118.977 36.8638 120.208C40.8988 123.122 43.6062 124.603 46.0434 125.228C48.3638 125.824 49.9665 125.86 52.2809 125.368C55.0428 124.78 57.0967 123.603 64.3248 118.466L66.6661 116.803L66.6699 122.476C66.6739 128.559 66.7693 129.102 67.9529 129.769C68.7443 130.215 69.7489 130.003 70.4555 129.242C70.9468 128.712 70.9688 128.371 70.9688 121.282V113.875L72.999 112.295C80.4117 106.527 84.6517 99.3137 86.68 89.0192C87.7165 83.7585 87.9686 80.7785 88.1091 72.1256L88.2473 63.6169L89.7231 61.4134C100.307 45.6115 99.3239 29.2983 86.9149 14.8265C84.2801 11.7538 80.1401 8.09816 77.5799 6.58377C75.0372 5.0798 72.7034 4.45321 69.5768 4.43467C65.7815 4.41205 62.606 5.30315 59.2585 7.33004C58.3364 7.88828 57.4647 8.34513 57.3214 8.34513C56.7955 8.34513 56.2842 6.78831 56.0124 4.35818C55.6163 0.820469 55.6016 0.772953 54.779 0.345826C54.0873 -0.0132055 52.4563 -0.105695 50.9736 0.129848ZM28.4473 40.7586C34.5334 42.573 40.0985 43.2268 49.455 43.2268C58.9087 43.2268 63.9449 42.6393 70.1789 40.8099C72.4563 40.1416 73.0797 40.0565 74.8613 40.1711C78.5984 40.411 81.5704 42.3555 83.0217 45.5101L83.7506 47.0941V63.2289C83.7506 80.3496 83.6818 81.6833 82.4742 87.9187C80.8893 96.1025 77.9727 101.821 72.9899 106.514C70.6765 108.693 69.0042 109.978 64.0085 113.414C62.1988 114.659 59.693 116.421 58.4402 117.33C55.3179 119.596 52.4019 121.073 50.6094 121.298C47.4678 121.692 45.0752 120.717 39.5297 116.782C37.5084 115.348 34.2831 113.088 32.3623 111.76C24.0871 106.04 20.2189 101.268 17.7081 93.6792C15.2836 86.3512 14.6792 79.288 14.8407 60.1798C14.9533 46.857 14.9821 46.5656 16.3942 44.4297C18.9551 40.5566 23.3148 39.2287 28.4473 40.7586Z"
                                        fill="black"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    width="97"
                                    height="131"
                                    viewBox="0 0 97 131"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M50.9738 0.129827C52.4565 -0.105996 54.0878 -0.0128339 54.7795 0.346623C55.6018 0.774101 55.6169 0.821846 56.0129 4.36322C56.2847 6.79586 56.7956 8.35492 57.3215 8.35541C57.4648 8.35541 58.3369 7.89771 59.259 7.33881C62.6065 5.30957 65.7821 4.41773 69.5774 4.44037C72.7038 4.45898 75.0377 5.08603 77.5803 6.59174C80.1404 8.10793 84.2805 11.7674 86.9152 14.8437C99.3241 29.3326 100.308 45.6656 89.7238 61.4863L88.2473 63.6923L88.1096 72.2109C87.9691 80.874 87.7164 83.858 86.6799 89.1249C85.0966 97.1701 82.1654 103.335 77.4299 108.4C75.6062 111.551 73.4471 114.725 70.969 117.612V121.426C70.969 128.521 70.9471 128.865 70.4563 129.394C69.7496 130.157 68.7448 130.369 67.9533 129.923C66.7698 129.255 66.6742 128.711 66.6701 122.621L66.6692 121.998C61.8182 126.276 56.0497 129.207 49.4475 129.207C41.9627 129.207 36.3484 126.829 32.2385 123.53C32.2157 128.539 31.9886 129.48 30.884 129.986C30.1173 130.337 29.1106 130.112 28.4416 129.439C27.9668 128.962 27.9416 128.566 27.9416 121.575V119.138C25.6695 116.199 24.2064 113.064 23.3801 110.402C17.3559 104.846 13.9493 98.4127 12.1545 89.3789C11.0717 83.929 10.7172 79.971 10.5598 71.5712L10.4113 63.6855L8.53829 61.7773C4.76352 57.9334 2.0062 53.2253 0.706261 48.4033C0.112051 46.2 0.0219001 45.3748 0.00215892 41.9345C-0.0223794 37.723 0.145079 36.5816 1.4006 32.3955C2.83518 27.6124 6.76769 20.7303 10.2453 16.916C11.5771 15.4551 11.9069 15.2236 12.6623 15.2236C13.8292 15.2236 14.7823 16.1654 14.7736 17.3105C14.7698 17.8228 14.4255 18.6889 13.8879 19.5371C13.4046 20.2994 13.0085 21.0569 13.008 21.2216C13.008 21.6658 14.3379 21.8494 15.4074 21.5527C16.0908 21.3629 17.3934 20.2981 20.1828 17.6484C27.6592 10.5473 32.2691 7.15712 38.6984 4.03217C42.9824 1.95002 46.436 0.852039 50.9738 0.129827ZM58.3518 96.8105C52.1377 96.8105 42.0588 96.4316 38.7434 97.0947C35.428 97.7578 30.8471 101.168 31.3547 103.062C31.9893 105.431 38.7433 115.756 41.1115 116.229C44.4269 116.608 46.3079 116.229 46.5109 115.472C46.8358 114.259 44.0481 110.451 43.7639 109.504C43.4798 108.557 43.9724 107.136 45.9426 107.136H53.1418C54.0533 107.262 55.6247 107.761 54.6184 109.504C53.3605 111.683 52.384 114.24 52.2893 115.472C52.1946 116.703 57.4991 116.798 58.3518 116.229C62.0461 112.535 67.5402 104.483 67.5402 102.873C67.5402 101.263 62.7092 96.8105 58.3518 96.8105ZM74.8615 40.2187C73.08 40.104 72.4563 40.1893 70.1789 40.8583C63.9451 42.6899 58.9088 43.2783 49.4553 43.2783C40.0988 43.2783 34.5336 42.6232 28.4475 40.8066C23.3152 39.275 18.9556 40.6048 16.3947 44.4824C14.9827 46.6208 14.9537 46.9126 14.841 60.2509C14.7688 68.8102 14.8511 74.9558 15.1799 79.791C18.668 86.4961 25.6338 96.6055 28.4182 95.8632C31.6388 92.2636 35.7121 89.2324 38.7434 89.2324C41.1683 89.2324 46.5109 88.664 49.4475 88.664C52.384 88.664 57.6887 88.664 60.6252 89.2324C64.7931 90.0391 69.4347 94.3476 70.9504 96.2421C72.9397 95.2949 83.3596 84.155 83.3596 82.0331V82.457C83.7187 78.972 83.7512 74.5243 83.7512 63.3037V47.1503L83.0217 45.5644C81.5704 42.4061 78.5986 40.4588 74.8615 40.2187Z"
                                        fill="black"
                                    />
                                </svg>
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
                    class="btn-optionals"
                    @click="showAddons = true"
                >
                    <span class="btn-optionals-icon" aria-hidden="true">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    ADICIONAR OPCIONAIS
                </button>

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
                    class="btn btn-plan-submit"
                    :disabled="hasActiveServicePlanSubscription"
                >
                    {{ planBuilderSubmitLabel }}
                </button>

                <p v-if="isGuest && !showSummary" class="plan-guest-login mt-3 mb-0">
                    Já tem uma conta,
                    <Link :href="loginUrl">entre aqui</Link>
                </p>
            </template>

            <div
                v-if="showSummary && selectedPackage"
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
                            class="back-button border-0"
                            :disabled="checkoutForm.processing"
                            @click="editPlan"
                        >
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            Voltar
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
                                :name="barbershopName"
                                :photo-url="barbershopPhotoUrl"
                                size="lg"
                            />
                            <div class="perfil-info">
                                <h3 class="barbershop-profile-name mb-1">
                                    {{ barbershopName }}
                                </h3>
                                <p class="barbershop-profile-meta mb-0">
                                    @{{ barbershopUsername }}
                                </p>
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

                    <div class="carrinho-total mt-3">
                        <p class="h5 fw-bold mb-0">
                            Total: {{ formattedTotal }}/mês
                        </p>
                    </div>

                    <fieldset class="mt-4">
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

                    <InputError
                        class="mt-3 mb-0"
                        :message="checkoutForm.errors.checkout"
                    />

                    <p
                        v-if="!isOwner && !mercadopagoConfigured && (isGuest || isAuthenticated)"
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

                        <InputError
                            class="mt-3 mb-0"
                            :message="guestRegisterForm.errors.checkout"
                        />

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
                                mercadopagoConfigured &&
                                !isOwner &&
                                paymentMethod
                            "
                            class="col-12 col-md-6"
                        >
                            <button
                                type="button"
                                class="btn btn-plan-submit w-100"
                                :disabled="
                                    checkoutForm.processing ||
                                    hasActiveServicePlanSubscription
                                "
                                @click="confirmCheckoutPayment"
                            >
                                {{
                                    checkoutForm.processing
                                        ? 'REDIRECIONANDO...'
                                        : 'CONFIRMAR PAGAMENTO'
                                }}
                            </button>
                        </div>

                        <div
                            v-else-if="isOwner && paymentMethod"
                            class="col-12 col-md-6"
                        >
                            <button
                                type="button"
                                class="btn btn-plan-submit w-100"
                                disabled
                            >
                                CONFIRMAR PAGAMENTO
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
