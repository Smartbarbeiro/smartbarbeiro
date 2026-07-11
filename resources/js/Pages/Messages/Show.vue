<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="message.subject" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="messages" title="Mensagem" />
        </template>

        <DashboardContentCard
            icon="messages"
            :title="message.subject"
            :description="`De ${message.barbershop.name} · ${new Date(message.created_at).toLocaleString('pt-BR')}`"
        >
            <div class="mb-4">
                <Link
                    :href="message.is_sender ? route('messages.compose') : route('messages.inbox')"
                    class="btn btn-outline-secondary btn-sm"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Voltar
                </Link>
            </div>

            <div class="border border-secondary-subtle rounded p-4 mb-4">
                <p class="mb-0" style="white-space: pre-wrap">{{ message.body }}</p>
            </div>

            <div v-if="message.is_sender" class="border-top border-secondary-subtle pt-4">
                <h3 class="h6 fw-semibold mb-3">Destinatários</h3>
                <div class="table-responsive">
                    <table class="table glass-table table-dark table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Cliente</th>
                                <th scope="col">Lida</th>
                                <th scope="col">E-mail enviado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="recipient in message.recipients" :key="recipient.id">
                                <td>
                                    <p class="fw-medium mb-0">{{ recipient.name }}</p>
                                    <p class="text-secondary small mb-0">{{ recipient.email }}</p>
                                </td>
                                <td class="text-secondary">
                                    {{
                                        recipient.read_at
                                            ? new Date(recipient.read_at).toLocaleString('pt-BR')
                                            : 'Não lida'
                                    }}
                                </td>
                                <td class="text-secondary">
                                    {{
                                        recipient.email_sent_at
                                            ? new Date(recipient.email_sent_at).toLocaleString('pt-BR')
                                            : '—'
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
