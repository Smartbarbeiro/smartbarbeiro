<script setup>
import InputError from '@/Components/InputError.vue';
import OAuthGoogleButton from '@/Components/OAuthGoogleButton.vue';
import RegisterPlanCard from '@/Components/RegisterPlanCard.vue';
import RegistrationFireworksOverlay from '@/Components/RegistrationFireworksOverlay.vue';
import StepSignupForm from '@/Components/StepSignupForm.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    redirect: {
        type: String,
        default: null,
    },
    isCustomerSignup: {
        type: Boolean,
        default: false,
    },
    celebrateRegistration: {
        type: Boolean,
        default: false,
    },
    redirectTo: {
        type: String,
        default: null,
    },
    platformPlan: {
        type: Object,
        default: null,
    },
    oauthGoogleEnabled: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    name: '',
    username: '',
    cpf: '',
    cpf_cnpj: '',
    email: '',
    password: '',
    password_confirmation: '',
    redirect: props.redirect,
});

const page = usePage();
const oauthError = computed(() => page.props.errors?.email ?? null);

const steps = computed(() => {
    const fields = [
        {
            key: 'name',
            type: 'text',
            placeholder: 'DIGITE SEU NOME AQUI',
            icon: 'bi bi-person',
            autocomplete: 'name',
            required: true,
            emptyMessage: 'Informe seu nome para continuar.',
        },
    ];

    if (!props.isCustomerSignup) {
        fields.push({
            key: 'cpf_cnpj',
            type: 'text',
            placeholder: 'DIGITE SEU CPF OU CNPJ AQUI',
            icon: 'bi bi-card-text',
            autocomplete: 'off',
            inputmode: 'numeric',
            required: true,
            emptyMessage: 'Informe seu CPF ou CNPJ para continuar.',
        });
        fields.push({
            key: 'username',
            type: 'text',
            placeholder: 'NOME DA BARBEARIA',
            icon: 'bi bi-shop',
            autocomplete: 'username',
            required: true,
            emptyMessage: 'Informe o nome da barbearia para continuar.',
        });
    } else {
        fields.push({
            key: 'cpf',
            type: 'text',
            placeholder: 'DIGITE SEU CPF AQUI',
            icon: 'bi bi-card-text',
            autocomplete: 'off',
            inputmode: 'numeric',
            required: true,
            emptyMessage: 'Informe seu CPF para continuar.',
        });
    }

    fields.push(
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
    );

    return fields;
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const panelTitle = 'Cadastre-se';

const panelSubtitle = computed(() =>
    props.isCustomerSignup
        ? 'Preencha o formulário para criar sua conta.'
        : 'Preencha com seus dados para começar a fidelizar seus clientes !',
);

const formPanelRef = ref(null);
const signupFormRef = ref(null);
const showCelebration = ref(false);

const startCelebration = () => {
    showCelebration.value = true;
};

const finishCelebration = () => {
    if (props.redirectTo) {
        router.visit(props.redirectTo);
    }
};

onMounted(() => {
    if (props.celebrateRegistration) {
        startCelebration();
    }
});

watch(
    () => props.celebrateRegistration,
    (value) => {
        if (value) {
            startCelebration();
        }
    },
);

const focusRegisterForm = () => {
    formPanelRef.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });
    signupFormRef.value?.focusFirstField();
};
</script>

<template>
    <MarketingLayout active-nav="register">
        <Head title="Cadastrar" />

        <section class="register-hero">
            <div
                class="register-hero__inner"
                :class="{ 'register-hero__layout': !isCustomerSignup }"
            >
                <RegisterPlanCard
                    v-if="!isCustomerSignup"
                    :platform-plan="platformPlan"
                    @start="focusRegisterForm"
                />

                <div
                    id="register-form-panel"
                    ref="formPanelRef"
                    class="login-panel register-panel"
                >
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">{{ panelTitle }}</h1>
                        <p class="login-panel__subtitle">
                            {{ panelSubtitle }}
                        </p>
                    </header>

                    <InputError class="mb-3" :message="oauthError" />

                    <OAuthGoogleButton
                        v-if="oauthGoogleEnabled"
                        class="mb-3"
                        intent="register"
                        :redirect="redirect"
                        :is-customer="isCustomerSignup"
                    />

                    <p
                        v-if="oauthGoogleEnabled"
                        class="oauth-divider text-center text-muted small mb-3"
                    >
                        ou cadastre-se com e-mail
                    </p>

                    <StepSignupForm
                        ref="signupFormRef"
                        :form="form"
                        :steps="steps"
                        :processing="form.processing"
                        plain
                        hide-header
                        @submit="submit"
                    />
                </div>
            </div>
        </section>

        <RegistrationFireworksOverlay
            v-if="showCelebration"
            @complete="finishCelebration"
        />
    </MarketingLayout>
</template>
