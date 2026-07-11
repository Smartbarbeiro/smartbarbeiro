<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    sentMessages: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    subject: '',
    body: '',
    send_email: true,
    audience: 'all',
    recipient_ids: [],
});

const allSelected = computed({
    get() {
        return (
            props.clients.length > 0
            && form.recipient_ids.length === props.clients.length
        );
    },
    set(value) {
        form.recipient_ids = value ? props.clients.map((client) => client.id) : [];
    },
});

const toggleRecipient = (clientId) => {
    if (form.recipient_ids.includes(clientId)) {
        form.recipient_ids = form.recipient_ids.filter((id) => id !== clientId);
    } else {
        form.recipient_ids = [...form.recipient_ids, clientId];
    }
};

const submit = () => {
    form.post(route('messages.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Mensagens" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="broadcast" title="Mensagens" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardContentCard
                icon="broadcast"
                title="Enviar mensagem"
                description="Envie uma mensagem interna e por e-mail para seus clientes cadastrados."
            >
                <div
                    v-if="clients.length === 0"
                    class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small"
                >
                    Você ainda não possui clientes cadastrados para receber mensagens.
                </div>

                <form v-else @submit.prevent="submit">
                    <div class="mb-3">
                        <InputLabel for="subject" value="Assunto" />
                        <TextInput
                            id="subject"
                            v-model="form.subject"
                            class="mt-1 w-100"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.subject" />
                    </div>

                    <div class="mb-3">
                        <InputLabel for="body" value="Mensagem" />
                        <textarea
                            id="body"
                            v-model="form.body"
                            class="form-control mt-1"
                            rows="6"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.body" />
                    </div>

                    <div class="mb-3">
                        <p class="form-label mb-2">Destinatários</p>
                        <div class="form-check mb-2">
                            <input
                                id="audience-all"
                                v-model="form.audience"
                                class="form-check-input"
                                type="radio"
                                value="all"
                            >
                            <label class="form-check-label" for="audience-all">
                                Todos os clientes ({{ clients.length }})
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input
                                id="audience-selected"
                                v-model="form.audience"
                                class="form-check-input"
                                type="radio"
                                value="selected"
                            >
                            <label class="form-check-label" for="audience-selected">
                                Selecionar clientes
                            </label>
                        </div>

                        <div
                            v-if="form.audience === 'selected'"
                            class="border border-secondary-subtle rounded p-3"
                        >
                            <div class="form-check mb-2">
                                <input
                                    id="select-all-clients"
                                    v-model="allSelected"
                                    class="form-check-input"
                                    type="checkbox"
                                >
                                <label class="form-check-label fw-semibold" for="select-all-clients">
                                    Marcar todos
                                </label>
                            </div>
                            <div
                                v-for="client in clients"
                                :key="client.id"
                                class="form-check"
                            >
                                <input
                                    :id="`client-${client.id}`"
                                    class="form-check-input"
                                    type="checkbox"
                                    :checked="form.recipient_ids.includes(client.id)"
                                    @change="toggleRecipient(client.id)"
                                >
                                <label class="form-check-label" :for="`client-${client.id}`">
                                    {{ client.name }}
                                    <span class="text-secondary">({{ client.email }})</span>
                                </label>
                            </div>
                            <InputError class="mt-2" :message="form.errors.recipient_ids" />
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input
                            id="send_email"
                            v-model="form.send_email"
                            class="form-check-input"
                            type="checkbox"
                        >
                        <label class="form-check-label" for="send_email">
                            Enviar também por e-mail
                        </label>
                    </div>

                    <PrimaryButton :disabled="form.processing">
                        Enviar mensagem
                    </PrimaryButton>
                </form>
            </DashboardContentCard>

            <DashboardContentCard
                icon="messages"
                title="Mensagens enviadas"
                description="Histórico das últimas mensagens enviadas."
            >
                <div
                    v-if="sentMessages.length === 0"
                    class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small"
                >
                    Nenhuma mensagem enviada ainda.
                </div>

                <div v-else class="table-responsive">
                    <table class="table glass-table table-dark table-hover table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Assunto</th>
                                <th scope="col">Destinatários</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Enviada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="message in sentMessages" :key="message.id">
                                <td>
                                    <Link
                                        :href="route('messages.show', message.id)"
                                        class="link-light text-decoration-none fw-medium"
                                    >
                                        {{ message.subject }}
                                    </Link>
                                </td>
                                <td>{{ message.recipients_count }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="message.send_email ? 'bg-success' : 'bg-secondary'"
                                    >
                                        {{ message.send_email ? 'Sim' : 'Não' }}
                                    </span>
                                </td>
                                <td class="text-secondary">
                                    {{
                                        new Date(message.created_at).toLocaleString('pt-BR')
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
