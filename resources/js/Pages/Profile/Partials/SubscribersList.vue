<script setup>
defineProps({
    subscribers: {
        type: Array,
        default: () => [],
    },
});

const statusClass = (status) => {
    if (status === 'authorized') return 'badge bg-success';
    if (status === 'cancelled') return 'badge bg-secondary';
    if (status === 'pending') return 'badge bg-secondary';
    return 'badge bg-secondary';
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Assinantes</h2>
            <p class="text-secondary small mb-0">
                Pessoas que assinaram seu perfil pago (últimos 50).
            </p>
        </header>

        <div
            v-if="subscribers.length === 0"
            class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small mt-3"
        >
            Nenhum assinante ainda.
        </div>

        <div v-else class="table-responsive mt-3">
            <table class="table table-dark table-hover table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th scope="col">Assinante</th>
                        <th scope="col">Status</th>
                        <th scope="col">Desde</th>
                        <th scope="col">Cancelada</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in subscribers" :key="row.id">
                        <td>
                            <p class="fw-medium mb-0">
                                {{ row.subscriber.name }}
                            </p>
                            <p class="text-secondary small mb-0">
                                {{ row.subscriber.email }}
                            </p>
                        </td>
                        <td>
                            <span
                                class="badge"
                                :class="statusClass(row.status)"
                            >
                                {{ row.status_label }}
                            </span>
                        </td>
                        <td class="text-secondary">
                            {{
                                new Date(row.created_at).toLocaleDateString('pt-BR')
                            }}
                        </td>
                        <td class="text-secondary">
                            {{
                                row.cancelled_at
                                    ? new Date(
                                          row.cancelled_at,
                                      ).toLocaleDateString('pt-BR')
                                    : '—'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
