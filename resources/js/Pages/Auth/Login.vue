<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthHeroLayout from '@/Layouts/AuthHeroLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    redirect: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect: props.redirect,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthHeroLayout active-nav="login">
        <Head title="Entrar" />

        <h2 class="auth-hero-title">Bem-vindo de volta.</h2>
        <p class="auth-hero-lead">
            Acesse sua conta para gerenciar sua barbearia.
        </p>

        <div
            v-if="status"
            class="alert alert-success auth-hero-alert mb-3"
            role="alert"
        >
            {{ status }}
        </div>

        <form class="auth-hero-form" @submit.prevent="submit">
            <div class="mb-3">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 w-100 auth-hero-input"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Senha" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 w-100 auth-hero-input"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="form-check mb-4">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <label class="form-check-label">Lembrar de mim</label>
            </div>

            <button
                type="submit"
                class="btn btn-light btn-lg auth-hero-submit w-100"
                :disabled="form.processing"
            >
                Entrar
            </button>

            <div
                v-if="canResetPassword"
                class="text-center mt-3"
            >
                <Link
                    :href="route('password.request')"
                    class="auth-hero-link"
                >
                    Esqueceu sua senha?
                </Link>
            </div>
        </form>
    </AuthHeroLayout>
</template>
