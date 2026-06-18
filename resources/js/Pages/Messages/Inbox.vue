<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    messages: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Mensagens" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Mensagens</h1>
        </template>

        <div class="app-card p-4">
            <header class="mb-3">
                <h2 class="h5 fw-semibold mb-1">Caixa de entrada</h2>
                <p class="text-secondary small mb-0">
                    Mensagens enviadas pelas barbearias que você frequenta.
                </p>
            </header>

            <div
                v-if="messages.length === 0"
                class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small"
            >
                Nenhuma mensagem recebida ainda.
            </div>

            <div v-else class="list-group list-group-flush">
                <Link
                    v-for="message in messages"
                    :key="message.id"
                    :href="route('messages.show', message.id)"
                    class="list-group-item list-group-item-action bg-transparent border-secondary-subtle px-0 py-3"
                >
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0">
                            <p class="fw-semibold mb-1">
                                <span
                                    v-if="!message.read_at"
                                    class="badge bg-primary me-2"
                                >
                                    Nova
                                </span>
                                {{ message.subject }}
                            </p>
                            <p class="text-secondary small mb-1">
                                {{ message.barbershop.name }}
                            </p>
                            <p class="text-secondary small mb-0 text-truncate">
                                {{ message.body }}
                            </p>
                        </div>
                        <span class="text-secondary small text-nowrap">
                            {{
                                new Date(message.created_at).toLocaleDateString('pt-BR')
                            }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
