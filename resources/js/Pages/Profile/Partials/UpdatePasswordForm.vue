<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Atualizar senha</h2>

            <p class="text-secondary small mb-0">
                Use uma senha longa e aleatória para manter sua conta segura.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-4">
            <div class="mb-3">
                <InputLabel for="current_password" value="Senha atual" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 w-100"
                    autocomplete="current-password"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2"
                />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Nova senha" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 w-100"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar senha"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 w-100"
                    autocomplete="new-password"
                />

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2"
                />
            </div>

            <div class="d-flex align-items-center gap-3">
                <PrimaryButton :disabled="form.processing">Salvar</PrimaryButton>

                <p
                    v-if="form.recentlySuccessful"
                    class="text-secondary small mb-0"
                >
                    Salvo.
                </p>
            </div>
        </form>
    </section>
</template>
