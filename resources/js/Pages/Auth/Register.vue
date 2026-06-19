<script setup>
import StepSignupForm from '@/Components/StepSignupForm.vue';
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
            key: 'username',
            type: 'text',
            placeholder: 'NOME DA BARBEARIA (OPCIONAL)',
            icon: 'bi bi-shop',
            autocomplete: 'username',
            required: false,
            skippable: true,
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
</script>

<template>
    <div class="register-plain">
        <Head title="Cadastrar" />

        <div class="register-plain__inner">
            <StepSignupForm
                :form="form"
                :steps="steps"
                :processing="form.processing"
                plain
                @submit="submit"
            />
        </div>
    </div>
</template>
