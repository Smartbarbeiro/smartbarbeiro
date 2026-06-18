<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const messages = computed(() => page.props.platformMessages ?? []);

const dismiss = (recipientId) => {
    router.patch(route('platform-messages.dismiss', recipientId), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString('pt-BR');
};
</script>

<template>
    <div
        v-if="messages.length > 0"
        class="platform-message-stack"
        aria-live="polite"
        aria-label="Mensagens do Smart Barbeiro"
    >
        <article
            v-for="message in messages"
            :key="message.recipient_id"
            class="platform-message-card"
        >
            <button
                type="button"
                class="platform-message-close"
                aria-label="Fechar mensagem"
                @click="dismiss(message.recipient_id)"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>

            <p class="platform-message-kicker">
                Smart Barbeiro
            </p>
            <h2 class="platform-message-title">
                {{ message.subject }}
            </h2>
            <p class="platform-message-body">
                {{ message.body }}
            </p>
            <p class="platform-message-meta">
                {{ formatDate(message.created_at) }}
            </p>
        </article>
    </div>
</template>
