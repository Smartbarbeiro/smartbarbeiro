<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = useForm({
    status: props.filters.status ?? '',
});

const updateForm = useForm({
    action: '',
});

const submitFilter = () => {
    filterForm.get(route('admin.acrylic-qr-orders.index'), {
        preserveState: true,
        replace: true,
    });
};

const statusClass = (status) => {
    if (status === 'pending') {
        return 'badge bg-secondary';
    }

    if (status === 'printed') {
        return 'badge bg-warning text-dark';
    }

    return 'badge bg-success';
};

const markPrinted = (orderId) => {
    updateForm.action = 'printed';
    updateForm.patch(route('admin.acrylic-qr-orders.update', orderId), {
        preserveScroll: true,
    });
};

const markShipped = (orderId) => {
    updateForm.action = 'shipped';
    updateForm.patch(route('admin.acrylic-qr-orders.update', orderId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pedidos QR acrílico" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="qr-acrylic" title="Pedidos QR acrílico" />
        </template>

        <DashboardContentCard
            icon="qr-acrylic"
            title="Pedidos QR acrílico"
            description="Receba pedidos de barbearias, confirme impressão e envio."
        >
            <div class="d-flex flex-wrap justify-content-end mb-4">
                <Link
                    :href="route('admin.users.index')"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Voltar aos usuários
                </Link>
            </div>

            <form
                class="row g-2 align-items-end mb-4"
                @submit.prevent="submitFilter"
            >
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select
                        id="status"
                        v-model="filterForm.status"
                        class="form-select"
                    >
                        <option value="">Todos</option>
                        <option value="pending">Aguardando impressão</option>
                        <option value="printed">Impresso</option>
                        <option value="shipped">Enviado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <PrimaryButton :disabled="filterForm.processing">
                        Filtrar
                    </PrimaryButton>
                </div>
            </form>

            <div
                v-if="orders.length === 0"
                class="border border-secondary-subtle border-dashed rounded p-4 text-center text-secondary small"
            >
                Nenhum pedido encontrado.
            </div>

            <div v-else class="table-responsive">
                <table class="table glass-table table-dark table-hover table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Barbearia</th>
                            <th scope="col">Destinatário</th>
                            <th scope="col">Endereço</th>
                            <th scope="col">Status</th>
                            <th scope="col">Pedido em</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id">
                            <td>
                                <p class="fw-medium mb-0">
                                    {{ order.barbershop.name }}
                                </p>
                                <p class="text-secondary small mb-0">
                                    @{{ order.barbershop.username }}
                                </p>
                            </td>
                            <td>
                                <p class="mb-0">{{ order.recipient_name }}</p>
                                <p class="text-secondary small mb-0">
                                    {{ order.phone }}
                                </p>
                            </td>
                            <td class="small text-secondary">
                                {{ order.formatted_address }}
                            </td>
                            <td>
                                <span
                                    class="badge"
                                    :class="statusClass(order.status)"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                {{
                                    new Date(order.created_at).toLocaleString('pt-BR')
                                }}
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a
                                        :href="
                                            route(
                                                'admin.acrylic-qr-orders.pdf',
                                                order.id,
                                            )
                                        "
                                        class="btn btn-outline-secondary btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Imprimir PDF
                                    </a>
                                    <SecondaryButton
                                        v-if="order.status === 'pending'"
                                        type="button"
                                        class="btn-sm"
                                        :disabled="updateForm.processing"
                                        @click="markPrinted(order.id)"
                                    >
                                        Impresso
                                    </SecondaryButton>
                                    <SecondaryButton
                                        v-if="order.status === 'printed'"
                                        type="button"
                                        class="btn-sm"
                                        :disabled="updateForm.processing"
                                        @click="markShipped(order.id)"
                                    >
                                        Enviado
                                    </SecondaryButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
