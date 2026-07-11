<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    subscriptionId: {
        type: Number,
        required: true,
    },
    creatorName: {
        type: String,
        required: true,
    },
    buttonLabel: {
        type: String,
        default: 'Cancelar assinatura',
    },
    compact: {
        type: Boolean,
        default: false,
    },
    destroyRoute: {
        type: String,
        default: null,
    },
});

const confirming = ref(false);
const form = useForm({});

const openModal = () => {
    confirming.value = true;
};

const closeModal = () => {
    confirming.value = false;
    form.clearErrors();
};

const cancelSubscription = () => {
    const targetRoute =
        props.destroyRoute ??
        route('subscriptions.destroy', props.subscriptionId);

    form.delete(targetRoute, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <div>
        <button
            v-if="compact"
            type="button"
            class="btn btn-link btn-sm p-0 cancel-subscription-link"
            @click="openModal"
        >
            {{ buttonLabel }}
        </button>
        <DangerButton v-else @click="openModal">{{ buttonLabel }}</DangerButton>

        <Modal :show="confirming" @close="closeModal">
            <div class="p-4">
                <h2 class="h5 fw-semibold">Cancelar assinatura?</h2>

                <p class="text-secondary small mt-2 mb-0">
                    Você perderá o acesso ao perfil de {{ creatorName }} quando o
                    período de cobrança atual terminar. O Mercado Pago deixará de
                    fazer cobranças mensais futuras.
                </p>

                <InputError class="mt-3" :message="form.errors.cancel" />

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <SecondaryButton @click="closeModal">
                        Manter assinatura
                    </SecondaryButton>

                    <DangerButton
                        :disabled="form.processing"
                        @click="cancelSubscription"
                    >
                        Confirmar cancelamento
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
