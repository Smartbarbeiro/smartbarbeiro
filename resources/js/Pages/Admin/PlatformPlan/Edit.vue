<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
});

const flashStatus = computed(() => usePage().props.flash?.status);

const form = useForm({
    title: props.plan.title,
    description: props.plan.description ?? '',
    monthly_amount: props.plan.monthly_amount,
    is_active: props.plan.is_active,
});

const submit = () => {
    form.patch(route('admin.platform-plan.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Plano da plataforma" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="platform-plan" title="Plano da plataforma" />
        </template>

        <DashboardContentCard
            icon="platform-plan"
            title="Plano da plataforma"
            description="Plano único cobrado de todas as barbearias após o cadastro. Apenas administradores podem alterar o preço."
            card-class="dashboard-content-card--narrow"
        >
            <div class="d-flex flex-wrap justify-content-end mb-4">
                <Link
                    :href="route('admin.users.index')"
                    class="link-primary small"
                >
                    Voltar ao painel de controle
                </Link>
            </div>

            <div
                v-if="flashStatus === 'platform-plan-updated'"
                class="alert alert-success"
                role="alert"
            >
                Plano da plataforma atualizado.
            </div>

            <form class="d-flex flex-column gap-3 mt-3" @submit.prevent="submit">
                <div>
                    <InputLabel for="title" value="Título" />
                    <TextInput
                        id="title"
                        v-model="form.title"
                        class="mt-1"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.title" />
                </div>

                <div>
                    <InputLabel for="description" value="Descrição" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="form-control mt-1"
                        rows="3"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>

                <div>
                    <InputLabel for="monthly_amount" value="Valor mensal (R$)" />
                    <TextInput
                        id="monthly_amount"
                        v-model="form.monthly_amount"
                        type="number"
                        step="0.01"
                        min="1"
                        class="mt-1"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.monthly_amount"
                    />
                </div>

                <div class="form-check">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        class="form-check-input"
                        type="checkbox"
                    />
                    <label class="form-check-label" for="is_active">
                        Plano ativo para novos cadastros
                    </label>
                </div>

                <p v-if="plan.mercadopago_preapproval_plan_id" class="text-muted small mb-0">
                    ID Mercado Pago: {{ plan.mercadopago_preapproval_plan_id }}
                </p>
                <p
                    v-else-if="!plan.mercadopago_configured"
                    class="text-warning small mb-0"
                >
                    Configure MERCADOPAGO_ACCESS_TOKEN para sincronizar o plano
                    com o Mercado Pago.
                </p>

                <PrimaryButton :disabled="form.processing">
                    Salvar plano
                </PrimaryButton>
            </form>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
