<script setup>
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    barbershops: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
    sentMessages: {
        type: Array,
        default: () => [],
    },
});

const flashStatus = computed(() => usePage().props.flash?.status);

const selectableUsers = computed(() => [
    ...props.barbershops.map((user) => ({ ...user, type: 'Barbearia' })),
    ...props.clients.map((user) => ({ ...user, type: 'Cliente' })),
]);

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
            selectableUsers.value.length > 0
            && form.recipient_ids.length === selectableUsers.value.length
        );
    },
    set(value) {
        form.recipient_ids = value
            ? selectableUsers.value.map((user) => user.id)
            : [];
    },
});

const audienceCounts = computed(() => ({
    barbershops: props.barbershops.length,
    clients: props.clients.length,
    all: props.barbershops.length + props.clients.length,
}));

const toggleRecipient = (userId) => {
    if (form.recipient_ids.includes(userId)) {
        form.recipient_ids = form.recipient_ids.filter((id) => id !== userId);
    } else {
        form.recipient_ids = [...form.recipient_ids, userId];
    }
};

const audienceLabel = (audience) => {
    switch (audience) {
        case 'barbershops':
            return 'Barbearias';
        case 'clients':
            return 'Clientes';
        case 'all':
            return 'Todos';
        case 'selected':
            return 'Selecionados';
        default:
            return audience;
    }
};

const submit = () => {
    form.post(route('admin.messages.store'), {
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
            <DashboardAlert
                :show="flashStatus === 'admin-message-sent'"
                variant="success"
            >
                Mensagem enviada. Os destinatários receberão por e-mail e verão um
                aviso fixo na tela ao entrar no app.
            </DashboardAlert>

            <DashboardContentCard
                icon="broadcast"
                title="Enviar mensagem"
                description="Envie avisos para barbearias e clientes por e-mail e como pop-up fixo na tela."
            >
                <form @submit.prevent="submit">
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
                                id="audience-barbershops"
                                v-model="form.audience"
                                class="form-check-input"
                                type="radio"
                                value="barbershops"
                            >
                            <label class="form-check-label" for="audience-barbershops">
                                Todas as barbearias ({{ audienceCounts.barbershops }})
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input
                                id="audience-clients"
                                v-model="form.audience"
                                class="form-check-input"
                                type="radio"
                                value="clients"
                            >
                            <label class="form-check-label" for="audience-clients">
                                Todos os clientes ({{ audienceCounts.clients }})
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input
                                id="audience-all"
                                v-model="form.audience"
                                class="form-check-input"
                                type="radio"
                                value="all"
                            >
                            <label class="form-check-label" for="audience-all">
                                Barbearias e clientes ({{ audienceCounts.all }})
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
                                Selecionar destinatários
                            </label>
                        </div>

                        <div
                            v-if="form.audience === 'selected'"
                            class="border border-secondary-subtle rounded p-3"
                            style="max-height: 18rem; overflow-y: auto"
                        >
                            <div class="form-check mb-2">
                                <input
                                    id="select-all-users"
                                    v-model="allSelected"
                                    class="form-check-input"
                                    type="checkbox"
                                >
                                <label class="form-check-label fw-semibold" for="select-all-users">
                                    Marcar todos
                                </label>
                            </div>
                            <div
                                v-for="user in selectableUsers"
                                :key="user.id"
                                class="form-check"
                            >
                                <input
                                    :id="`user-${user.id}`"
                                    class="form-check-input"
                                    type="checkbox"
                                    :checked="form.recipient_ids.includes(user.id)"
                                    @change="toggleRecipient(user.id)"
                                >
                                <label class="form-check-label" :for="`user-${user.id}`">
                                    {{ user.name }}
                                    <span class="text-secondary">
                                        ({{ user.type }} · {{ user.email }})
                                    </span>
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
                description="Histórico de avisos enviados para barbearias e clientes."
            >
                <div
                    v-if="sentMessages.length === 0"
                    class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small"
                >
                    Nenhuma mensagem enviada ainda.
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-dark table-hover table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Assunto</th>
                                <th scope="col">Público</th>
                                <th scope="col">Destinatários</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Enviada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="message in sentMessages" :key="message.id">
                                <td class="fw-medium">{{ message.subject }}</td>
                                <td>{{ audienceLabel(message.audience) }}</td>
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
