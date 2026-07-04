<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <MarketingLayout active-nav="login">
        <Head title="Redefinir senha" />

        <section class="register-hero">
            <div class="register-hero__inner">
                <div class="login-panel">
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">Redefinir senha</h1>
                        <p class="login-panel__subtitle">
                            Escolha uma nova senha para acessar sua conta.
                        </p>
                    </header>

                    <form class="login-panel__form" @submit.prevent="submit">
                        <div class="mb-3">
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

                        <div class="mb-3">
                            <InputLabel for="password" value="Nova senha" />

                            <div class="input-group mt-1">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="btn btn-outline-dark login-panel__toggle-password"
                                    :aria-label="
                                        showPassword
                                            ? 'Ocultar senha'
                                            : 'Mostrar senha'
                                    "
                                    @click="showPassword = !showPassword"
                                >
                                    <i
                                        class="bi"
                                        :class="
                                            showPassword
                                                ? 'bi-eye-slash'
                                                : 'bi-eye'
                                        "
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            </div>

                            <InputError
                                class="mt-2"
                                :message="form.errors.password"
                            />
                        </div>

                        <div class="mb-4">
                            <InputLabel
                                for="password_confirmation"
                                value="Confirmar nova senha"
                            />

                            <div class="input-group mt-1">
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="
                                        showPasswordConfirmation
                                            ? 'text'
                                            : 'password'
                                    "
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="btn btn-outline-dark login-panel__toggle-password"
                                    :aria-label="
                                        showPasswordConfirmation
                                            ? 'Ocultar confirmação'
                                            : 'Mostrar confirmação'
                                    "
                                    @click="
                                        showPasswordConfirmation =
                                            !showPasswordConfirmation
                                    "
                                >
                                    <i
                                        class="bi"
                                        :class="
                                            showPasswordConfirmation
                                                ? 'bi-eye-slash'
                                                : 'bi-eye'
                                        "
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            </div>

                            <InputError
                                class="mt-2"
                                :message="form.errors.password_confirmation"
                            />
                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark btn-lg w-100 login-panel__submit"
                            :disabled="form.processing"
                        >
                            Salvar nova senha
                        </button>

                        <p class="login-panel__footer mb-0">
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
