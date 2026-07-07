<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    is_enabled: props.subscriptionPlan?.is_enabled ?? false,
    title: props.subscriptionPlan?.title ?? 'Acesso mensal ao perfil',
    description:
        props.subscriptionPlan?.description ??
        'Assine para ter acesso mensal ao conteúdo exclusivo do meu perfil.',
    monthly_amount: props.subscriptionPlan?.monthly_amount ?? 29.9,
});

const submit = () => {
    form.put(route('profile.subscription-plan.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Plano de assinatura</h2>
            <p class="text-secondary small mb-0">
                Defina se clientes precisam pagar uma mensalidade para acessar
                seu perfil público e configure título, descrição e preço.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-4">
            <div class="form-check mb-3">
                <input
                    id="is_enabled"
                    v-model="form.is_enabled"
                    type="checkbox"
                    class="form-check-input"
                />
                <InputLabel
                    for="is_enabled"
                    value="Exigir assinatura paga"
                    class="form-check-label"
                />
            </div>

            <div class="mb-3">
                <InputLabel for="plan_title" value="Título do plano" />
                <TextInput
                    id="plan_title"
                    v-model="form.title"
                    type="text"
                    class="mt-1 w-100"
                    required
                />
                <InputError class="mt-2" :message="form.errors.title" />
            </div>

            <div class="mb-3">
                <InputLabel for="plan_description" value="Descrição" />
                <textarea
                    id="plan_description"
                    v-model="form.description"
                    class="form-control mt-1"
                    rows="3"
                />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <div class="mb-3">
                <InputLabel for="monthly_amount" value="Preço mensal (BRL)" />
                <TextInput
                    id="monthly_amount"
                    v-model="form.monthly_amount"
                    type="number"
                    min="1"
                    step="0.01"
                    class="mt-1 w-100"
                    required
                />
                <InputError class="mt-2" :message="form.errors.monthly_amount" />
            </div>

            <InputError class="mb-3" :message="form.errors.mercadopago" />

            <div class="d-flex align-items-center gap-3">
                <PrimaryButton :disabled="form.processing || !mercadopagoConfigured">
                    Salvar plano
                </PrimaryButton>

                <p
                    v-if="$page.props.flash?.status === 'subscription-plan-updated'"
                    class="text-secondary small mb-0"
                >
                    Plano salvo.
                </p>
            </div>
        </form>
    </section>
</template>
