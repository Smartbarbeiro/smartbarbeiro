<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    barbershopUsername: {
        type: String,
        required: true,
    },
    preferredHaircutDay: {
        type: Number,
        default: null,
    },
});

const form = useForm({
    preferred_haircut_day: props.preferredHaircutDay ?? 1,
});

const dayOptions = computed(() =>
    Array.from({ length: 31 }, (_, index) => index + 1),
);

const submit = () => {
    form.patch(
        route('barbershop.preferred-haircut-day.update', {
            username: props.barbershopUsername,
        }),
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Modal :show="show" max-width="md" :closeable="false">
        <form class="p-4" @submit.prevent="submit">
            <h2 class="h5 fw-semibold mb-2">Dia preferido para o corte</h2>

            <p class="text-secondary small mb-4">
                Escolha o dia do mês em que prefere fazer o corte nesta
                barbearia. Você poderá alterar depois entrando em contato com a
                barbearia.
            </p>

            <div>
                <InputLabel
                    for="preferred_haircut_day"
                    value="Dia do mês"
                />

                <select
                    id="preferred_haircut_day"
                    v-model.number="form.preferred_haircut_day"
                    class="form-select mt-1"
                    required
                >
                    <option
                        v-for="day in dayOptions"
                        :key="day"
                        :value="day"
                    >
                        Dia {{ day }}
                    </option>
                </select>

                <InputError
                    class="mt-2"
                    :message="form.errors.preferred_haircut_day"
                />
            </div>

            <div class="d-flex justify-content-end mt-4">
                <PrimaryButton :disabled="form.processing">
                    Salvar preferência
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
