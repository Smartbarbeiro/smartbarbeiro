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
        default: 'Cancel subscription',
    },
    compact: {
        type: Boolean,
        default: false,
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
    form.delete(route('subscriptions.destroy', props.subscriptionId), {
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
            class="text-sm font-medium text-red-600 underline hover:text-red-500"
            @click="openModal"
        >
            {{ buttonLabel }}
        </button>
        <DangerButton v-else @click="openModal">{{ buttonLabel }}</DangerButton>

        <Modal :show="confirming" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Cancel subscription?
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    You will lose access to {{ creatorName }}'s profile when the
                    current billing period ends. Mercado Pago will stop future
                    monthly charges.
                </p>

                <InputError class="mt-3" :message="form.errors.cancel" />

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Keep subscription</SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="cancelSubscription"
                    >
                        Confirm cancellation
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
