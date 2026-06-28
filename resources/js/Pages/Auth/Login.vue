<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import OAuthGoogleButton from '@/Components/OAuthGoogleButton.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    oauthGoogleEnabled: {
        type: Boolean,
        default: false,
    },
});

const showPassword = ref(false);

const barbershopRedirect = computed(() =>
    props.redirect?.startsWith('/barbearias/') ? props.redirect : null,
);

const form = useForm({
    email: '',
    password: '',
    remember: false,
    ...(barbershopRedirect.value ? { redirect: barbershopRedirect.value } : {}),
});

const page = usePage();
const oauthError = computed(
    () => page.props.errors?.email ?? form.errors.email ?? null,
);
const showGoogleLogin = computed(
    () => props.oauthGoogleEnabled && !oauthError.value,
);

const submit = () => {
    form.post(route('login'), {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <MarketingLayout active-nav="login">
        <Head title="Entrar" />

        <section class="register-hero">
            <div class="register-hero__inner">
                <div class="login-panel">
                    <header class="login-panel__header">
                        <h1 class="login-panel__title">Entrar</h1>
                        <p class="login-panel__subtitle">
                            Acesse sua conta para gerenciar sua barbearia.
                        </p>
                    </header>

                    <div
                        v-if="status"
                        class="alert alert-success mb-3"
                        role="alert"
                    >
                        {{ status }}
                    </div>

                    <InputError class="mb-3" :message="oauthError" />

                    <OAuthGoogleButton
                        v-if="showGoogleLogin"
                        class="mb-3"
                        intent="login"
                        :redirect="barbershopRedirect"
                    />

                    <p
                        v-if="showGoogleLogin"
                        class="oauth-divider text-center text-muted small mb-3"
                    >
                        ou entre com e-mail
                    </p>

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
                            <InputLabel for="password" value="Senha" />

                            <div class="input-group mt-1">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control"
                                    required
                                    autocomplete="current-password"
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

                        <div class="form-check mb-4">
                            <Checkbox
                                name="remember"
                                v-model:checked="form.remember"
                            />
                            <label class="form-check-label">
                                Lembrar de mim
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark btn-lg w-100 login-panel__submit"
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
                                class="login-panel__link"
                            >
                                Esqueceu sua senha?
                            </Link>
                        </div>

                        <p class="login-panel__footer mb-0">
                            Não tem conta?
                            <Link
                                :href="
                                    route(
                                        'register',
                                        barbershopRedirect
                                            ? { redirect: barbershopRedirect }
                                            : {},
                                    )
                                "
                                class="login-panel__link"
                            >
                                Cadastrar
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
