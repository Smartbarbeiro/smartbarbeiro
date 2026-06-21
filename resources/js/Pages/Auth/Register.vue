<script setup>
import StepSignupForm from '@/Components/StepSignupForm.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    redirect: {
        type: String,
        default: null,
    },
    isCustomerSignup: {
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

const panelTitle = computed(() =>
    props.isCustomerSignup
        ? 'Cadastre-se'
        : 'Cadastre sua Barbearia',
);

const panelSubtitle = computed(() =>
    props.isCustomerSignup
        ? 'Preencha o formulário para criar sua conta.'
        : 'Preencha o formulário para criar seu perfil.',
);
</script>

<template>
    <MarketingLayout active-nav="register">
        <Head title="Cadastrar" />

        <section class="register-hero">
            <div class="register-hero__inner">
                <div class="login-panel register-panel">
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">{{ panelTitle }}</h1>
                        <p class="login-panel__subtitle">
                            {{ panelSubtitle }}
                        </p>
                    </header>

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
        </section>
    </MarketingLayout>
</template>
