<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <MarketingLayout active-nav="login">
        <Head title="Esqueceu a senha" />

        <section class="register-hero">
            <div class="register-hero__inner">
                <div class="login-panel">
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">Esqueceu a senha?</h1>
                        <p class="login-panel__subtitle">
                            Informe seu e-mail e enviaremos um link para redefinir
                            sua senha.
                        </p>
                    </header>

                    <div
                        v-if="status"
                        class="alert alert-success mb-3"
                        role="alert"
                    >
                        {{ status }}
                    </div>

                    <form class="login-panel__form" @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="email" value="E-mail" />

                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="form-control mt-1"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.email"
                            />
                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark btn-lg w-100 login-panel__submit"
                            :disabled="form.processing"
                        >
                            Enviar link de redefinição
                        </button>

                        <p class="login-panel__footer mb-0">
                            Lembrou a senha?
                            <Link
                                :href="route('login')"
                                class="login-panel__link"
                            >
                                Voltar para entrar
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
