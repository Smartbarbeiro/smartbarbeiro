<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
    <GuestLayout>
        <Head title="Entrar" />

        <div v-if="status" class="alert alert-success mb-3" role="alert">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 w-100"
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
                    class="mt-1 w-100"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="form-check mb-3">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <label class="form-check-label">Lembrar de mim</label>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-3">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="btn btn-link link-secondary p-0"
                >
                    Esqueceu sua senha?
                </Link>

                <PrimaryButton :disabled="form.processing">
                    Entrar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
