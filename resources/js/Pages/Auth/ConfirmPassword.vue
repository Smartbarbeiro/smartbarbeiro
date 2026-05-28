<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar senha" />

        <p class="text-secondary small mb-3">
            Esta é uma área segura do aplicativo. Confirme sua senha antes de
            continuar.
        </p>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <InputLabel for="password" value="Senha" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 w-100"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="d-flex justify-content-end">
                <PrimaryButton :disabled="form.processing">
                    Confirmar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
