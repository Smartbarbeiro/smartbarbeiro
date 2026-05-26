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
    title: props.subscriptionPlan?.title ?? 'Monthly profile access',
    description:
        props.subscriptionPlan?.description ??
        'Subscribe for monthly access to my exclusive profile content.',
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
            <h2 class="text-lg font-medium text-gray-900">
                Paid profile access
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Charge a monthly fee via Mercado Pago so only subscribers can
                view your public profile.
            </p>
        </header>

        <div
            v-if="!mercadopagoConfigured"
            class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
        >
            Add <code class="rounded bg-amber-100 px-1">MERCADOPAGO_ACCESS_TOKEN</code>
            to your <code class="rounded bg-amber-100 px-1">.env</code> file to
            enable payments.
        </div>

        <div
            v-else-if="form.is_enabled"
            class="mt-4 rounded-md border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900"
        >
            <p class="font-medium">Share your profile link (subscribers pay here)</p>
            <p class="mt-1 break-all font-mono text-xs">{{ subscribeUrl }}</p>
            <button
                type="button"
                class="mt-2 text-sm font-medium text-indigo-700 underline"
                @click="copySubscribeLink"
            >
                Copy link
            </button>
            <p class="mt-2 text-indigo-800">
                Active subscribers: {{ activeSubscribersCount }}
            </p>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="flex items-center gap-2">
                <input
                    id="is_enabled"
                    v-model="form.is_enabled"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <InputLabel for="is_enabled" value="Require paid subscription" />
            </div>

            <div>
                <InputLabel for="plan_title" value="Plan title" />
                <TextInput
                    id="plan_title"
                    v-model="form.title"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.title" />
            </div>

            <div>
                <InputLabel for="plan_description" value="Description" />
                <textarea
                    id="plan_description"
                    v-model="form.description"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows="3"
                />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <div>
                <InputLabel for="monthly_amount" value="Monthly price (BRL)" />
                <TextInput
                    id="monthly_amount"
                    v-model="form.monthly_amount"
                    type="number"
                    min="1"
                    step="0.01"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.monthly_amount" />
            </div>

            <InputError class="mt-2" :message="form.errors.mercadopago" />

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing || !mercadopagoConfigured">
                    Save plan
                </PrimaryButton>

                <p
                    v-if="$page.props.flash?.status === 'subscription-plan-updated'"
                    class="text-sm text-gray-600"
                >
                    Plan saved.
                </p>
            </div>
        </form>
    </section>
</template>
