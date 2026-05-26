<script setup>
defineProps({
    subscribers: {
        type: Array,
        default: () => [],
    },
});

const statusClass = (status) => {
    if (status === 'authorized') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-gray-100 text-gray-700';
    if (status === 'pending') return 'bg-amber-100 text-amber-800';
    return 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Subscribers</h2>
            <p class="mt-1 text-sm text-gray-600">
                People who subscribed to your paid profile (latest 50).
            </p>
        </header>

        <div
            v-if="subscribers.length === 0"
            class="mt-4 rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500"
        >
            No subscribers yet.
        </div>

        <div v-else class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-4 py-2 text-left font-medium text-gray-600"
                        >
                            Subscriber
                        </th>
                        <th
                            class="px-4 py-2 text-left font-medium text-gray-600"
                        >
                            Status
                        </th>
                        <th
                            class="px-4 py-2 text-left font-medium text-gray-600"
                        >
                            Since
                        </th>
                        <th
                            class="px-4 py-2 text-left font-medium text-gray-600"
                        >
                            Cancelled
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="row in subscribers" :key="row.id">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">
                                {{ row.subscriber.name }}
                            </p>
                            <p class="text-gray-500">
                                {{ row.subscriber.email }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusClass(row.status)"
                            >
                                {{ row.status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{
                                new Date(row.created_at).toLocaleDateString()
                            }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{
                                row.cancelled_at
                                    ? new Date(
                                          row.cancelled_at,
                                      ).toLocaleDateString()
                                    : '—'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
