<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Cadastrar" />

        <form @submit.prevent="submit">
            <div
                v-if="isCustomerSignup"
                class="alert alert-info mb-3"
                role="alert"
            >
                Crie sua conta para se cadastrar nesta barbearia. Você não terá
                uma página de perfil público.
            </div>

            <div class="mb-3">
                <InputLabel for="name" value="Nome" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 w-100"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div v-if="!isCustomerSignup" class="mb-3">
                <InputLabel for="username" value="Nome de usuário (opcional)" />

                <TextInput
                    id="username"
                    type="text"
                    class="mt-1 w-100"
                    v-model="form.username"
                    autocomplete="username"
                />

                <p class="form-text">
                    Deixe em branco para gerar a partir do seu nome. A página da sua barbearia ficará em
                    /barbearias/seu-usuario
                </p>

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div class="mb-3">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 w-100"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Senha" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 w-100"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar senha"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 w-100"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="d-flex align-items-center justify-content-end gap-3">
                <Link
                    :href="route('login', isCustomerSignup ? { redirect } : {})"
                    class="btn btn-link link-secondary p-0"
                >
                    Já está cadastrado?
                </Link>

                <PrimaryButton :disabled="form.processing">
                    Cadastrar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
