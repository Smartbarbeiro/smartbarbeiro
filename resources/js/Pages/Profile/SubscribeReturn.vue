<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    creator: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assinatura" />

        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Status da assinatura</h1>
        </template>

        <div class="app-card p-4">
            <p
                v-if="subscription?.is_active"
                class="text-success mb-3"
            >
                Sua assinatura de {{ creator.name }} está ativa. Agora você
                tem acesso ao perfil.
            </p>
            <p v-else class="text-warning mb-3">
                Estamos confirmando seu pagamento com o Mercado Pago. Se você
                concluiu o checkout, o acesso será liberado em breve.
            </p>

            <Link
                :href="
                    route('profile.public', {
                        username: creator.username,
                    })
                "
                class="btn btn-primary btn-sm"
            >
                Ir para o perfil
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
