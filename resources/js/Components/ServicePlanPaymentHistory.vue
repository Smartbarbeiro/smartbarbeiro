<script setup>
const props = defineProps({
    paymentHistory: {
        type: Array,
        default: () => [],
    },
});

const paymentStatusClass = (status) => {
    if (status === 'paid') return 'badge bg-success';
    if (status === 'failed' || status === 'overdue') return 'badge bg-danger';
    return 'badge bg-secondary';
};

const monthRows = (yearGroup) => {
    const rows = [];

    for (let month = 1; month <= 12; month += 1) {
        const payment = yearGroup.months?.[month] ?? yearGroup.months?.[String(month)];

        if (payment) {
            rows.push(payment);
        }
    }

    return rows;
};
</script>

<template>
    <div v-if="paymentHistory.length > 0" class="service-plan-payment-history mt-4">
        <h4 class="h6 fw-semibold mb-3">Pagamentos mensais</h4>

        <div
            v-for="yearGroup in paymentHistory"
            :key="yearGroup.year"
            class="mb-4"
        >
            <p class="small text-secondary fw-semibold mb-2">{{ yearGroup.year }}</p>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Competência</th>
                            <th scope="col">Valor</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Nota fiscal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="payment in monthRows(yearGroup)"
                            :key="payment.id"
                        >
                            <td>{{ payment.period_label }}</td>
                            <td>{{ payment.formatted_amount }}</td>
                            <td>
                                <span
                                    class="badge"
                                    :class="paymentStatusClass(payment.status)"
                                >
                                    {{ payment.status_label }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a
                                    v-if="payment.download_url"
                                    :href="payment.download_url"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i
                                        class="bi bi-file-earmark-pdf me-1"
                                        aria-hidden="true"
                                    ></i>
                                    Baixar PDF
                                </a>
                                <span v-else class="text-secondary small">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
