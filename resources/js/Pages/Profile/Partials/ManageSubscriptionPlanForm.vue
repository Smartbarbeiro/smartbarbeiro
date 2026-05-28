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
    subscribeUrl: {
        type: String,
        required: true,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
    activeSubscribersCount: {
        type: Number,
        default: 0,
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

const copySubscribeLink = async () => {
    await navigator.clipboard.writeText(props.subscribeUrl);
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Acesso pago ao perfil</h2>
            <p class="text-secondary small mb-0">
                Cobre uma mensalidade via Mercado Pago para que apenas assinantes
                possam ver seu perfil público.
            </p>
        </header>

        <div
            v-if="!mercadopagoConfigured"
            class="alert alert-warning mt-3 mb-0"
            role="alert"
        >
            Adicione <code>MERCADOPAGO_ACCESS_TOKEN</code>
            ao seu arquivo <code>.env</code> para habilitar pagamentos.
        </div>

        <div
            v-else-if="form.is_enabled"
            class="alert alert-info mt-3 mb-0"
            role="alert"
        >
            <p class="fw-medium mb-1">Compartilhe o link do seu perfil (assinantes pagam aqui)</p>
            <p class="font-monospace small text-break mb-2">{{ subscribeUrl }}</p>
            <button
                type="button"
                class="btn btn-link link-primary p-0"
                @click="copySubscribeLink"
            >
                Copiar link
            </button>
            <p class="mb-0 mt-2">
                Assinantes ativos: {{ activeSubscribersCount }}
            </p>
        </div>

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
