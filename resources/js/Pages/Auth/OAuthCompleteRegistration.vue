<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
    name: props.oauthUser.name ?? '',
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
    'Informe os dados abaixo para finalizar sua conta com Google.',
);

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

                    <form class="login-panel__form" @submit.prevent="submit">
                        <div class="mb-3">
                            <InputLabel for="name" value="Nome" />
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="form-control mt-1"
                                required
                                autocomplete="name"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mb-3">
                            <InputLabel for="email" value="E-mail" />
                            <input
                                id="email"
                                :value="oauthUser.email"
                                type="email"
                                class="form-control mt-1"
                                disabled
                                readonly
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div v-if="isCustomerSignup" class="mb-3">
                            <InputLabel for="cpf" value="CPF" />
                            <input
                                id="cpf"
                                v-model="form.cpf"
                                type="text"
                                class="form-control mt-1"
                                required
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="000.000.000-00"
                            />
                            <InputError class="mt-2" :message="form.errors.cpf" />
                        </div>

                        <template v-else>
                            <div class="mb-3">
                                <InputLabel for="cpf_cnpj" value="CPF ou CNPJ" />
                                <input
                                    id="cpf_cnpj"
                                    v-model="form.cpf_cnpj"
                                    type="text"
                                    class="form-control mt-1"
                                    required
                                    inputmode="numeric"
                                    autocomplete="off"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.cpf_cnpj"
                                />
                            </div>

                            <div class="mb-3">
                                <InputLabel
                                    for="username"
                                    value="Nome da barbearia (URL)"
                                />
                                <input
                                    id="username"
                                    v-model="form.username"
                                    type="text"
                                    class="form-control mt-1"
                                    required
                                    autocomplete="username"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.username"
                                />
                            </div>
                        </template>

                        <button
                            type="submit"
                            class="btn btn-dark btn-lg w-100 login-panel__submit"
                            :disabled="form.processing"
                        >
                            Finalizar cadastro
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
