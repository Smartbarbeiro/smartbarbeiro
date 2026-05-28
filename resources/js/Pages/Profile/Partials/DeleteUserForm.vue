<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Excluir conta</h2>

            <p class="text-secondary small mb-0">
                Ao excluir sua conta, todos os recursos e dados serão removidos
                permanentemente. Antes de excluir, baixe qualquer dado que deseja
                manter.
            </p>
        </header>

        <DangerButton class="mt-4" @click="confirmUserDeletion">
            Excluir conta
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-4">
                <h2 class="h5 fw-semibold">
                    Tem certeza de que deseja excluir sua conta?
                </h2>

                <p class="text-secondary small mt-2 mb-0">
                    Ao excluir sua conta, todos os recursos e dados serão removidos
                    permanentemente. Digite sua senha para confirmar que deseja
                    excluir sua conta permanentemente.
                </p>

                <div class="mt-4">
                    <InputLabel
                        for="password"
                        value="Senha"
                        class="visually-hidden"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 w-75"
                        placeholder="Senha"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Excluir conta
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
