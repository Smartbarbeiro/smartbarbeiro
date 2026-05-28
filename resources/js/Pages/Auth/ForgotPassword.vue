<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Esqueceu a senha" />

        <p class="text-secondary small mb-3">
            Esqueceu sua senha? Sem problemas. Informe seu endereço de e-mail
            e enviaremos um link para redefinir sua senha.
        </p>

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

            <div class="d-flex justify-content-end">
                <PrimaryButton :disabled="form.processing">
                    Enviar link de redefinição de senha
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
