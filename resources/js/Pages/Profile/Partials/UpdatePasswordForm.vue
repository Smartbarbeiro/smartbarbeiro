<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    embedded: {
        type: Boolean,
        default: false,
    },
});

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

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
        <header v-if="!embedded">
            <h2 class="h5 fw-semibold mb-1">Atualizar senha</h2>

            <p class="text-secondary small mb-0">
                Use uma senha longa e aleatória para manter sua conta segura.
            </p>
        </header>

        <div v-else class="mb-3">
            <h3 class="h6 fw-semibold mb-1">Atualizar senha</h3>
            <p class="text-secondary small mb-0">
                Use uma senha longa e aleatória para manter sua conta segura.
            </p>
        </div>

        <form @submit.prevent="updatePassword" :class="embedded ? '' : 'mt-4'">
            <div class="mb-3">
                <InputLabel for="current_password" value="Senha atual" />

                <div class="input-group mt-1">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :aria-label="showCurrentPassword ? 'Ocultar senha atual' : 'Mostrar senha atual'"
                        @click="showCurrentPassword = !showCurrentPassword"
                    >
                        <i
                            class="bi"
                            :class="showCurrentPassword ? 'bi-eye-slash' : 'bi-eye'"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2"
                />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Nova senha" />

                <div class="input-group mt-1">
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :aria-label="showPassword ? 'Ocultar nova senha' : 'Mostrar nova senha'"
                        @click="showPassword = !showPassword"
                    >
                        <i
                            class="bi"
                            :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar senha"
                />

                <div class="input-group mt-1">
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :aria-label="showPasswordConfirmation ? 'Ocultar confirmação de senha' : 'Mostrar confirmação de senha'"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                    >
                        <i
                            class="bi"
                            :class="showPasswordConfirmation ? 'bi-eye-slash' : 'bi-eye'"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2"
                />
            </div>

            <div class="d-flex align-items-center gap-3">
                <PrimaryButton :disabled="form.processing">Salvar senha</PrimaryButton>

                <p
                    v-if="form.recentlySuccessful"
                    class="text-secondary small mb-0"
                >
                    Senha atualizada.
                </p>
            </div>
        </form>
    </section>
</template>
