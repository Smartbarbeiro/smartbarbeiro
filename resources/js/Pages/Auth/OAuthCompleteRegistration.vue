<script setup>
import StepSignupForm from '@/Components/StepSignupForm.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    oauthUser: {
        type: Object,
        required: true,
    },
    isCustomerSignup: {
        type: Boolean,
        default: false,
    },
    redirect: {
        type: String,
        default: null,
    },
});

const form = useForm({
    cpf: '',
    cpf_cnpj: '',
    username: '',
});

const panelTitle = computed(() =>
    props.isCustomerSignup
        ? 'Complete seu cadastro'
        : 'Complete o cadastro da barbearia',
);

const panelSubtitle = computed(() =>
    props.isCustomerSignup
        ? 'Informe seu CPF para finalizar sua conta com Google.'
        : 'Informe o CPF/CNPJ e o nome da barbearia para finalizar com Google.',
);

const steps = computed(() => {
    if (props.isCustomerSignup) {
        return [
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
        ];
    }

    return [
        {
            key: 'cpf_cnpj',
            type: 'text',
            placeholder: 'DIGITE SEU CPF OU CNPJ AQUI',
            icon: 'bi bi-card-text',
            autocomplete: 'off',
            inputmode: 'numeric',
            required: true,
            emptyMessage: 'Informe seu CPF ou CNPJ para continuar.',
        },
        {
            key: 'username',
            type: 'text',
            placeholder: 'NOME DA BARBEARIA',
            icon: 'bi bi-shop',
            autocomplete: 'username',
            required: true,
            emptyMessage: 'Informe o nome da barbearia para continuar.',
        },
    ];
});

const submit = () => {
    form.post(route('register.oauth.complete.store'));
};
</script>

<template>
    <MarketingLayout active-nav="register">
        <Head title="Cadastrar com Google" />

        <section class="register-hero">
            <div class="register-hero__inner">
                <div class="login-panel register-panel">
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">{{ panelTitle }}</h1>
                        <p class="login-panel__subtitle">
                            {{ panelSubtitle }}
                        </p>
                    </header>

                    <div class="register-panel__body">
                        <p class="oauth-account-summary text-center mb-3">
                            Olá, <strong>{{ oauthUser.name }}</strong>
                        </p>

                        <StepSignupForm
                            :form="form"
                            :steps="steps"
                            :processing="form.processing"
                            plain
                            hide-header
                            @submit="submit"
                        />
                    </div>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
