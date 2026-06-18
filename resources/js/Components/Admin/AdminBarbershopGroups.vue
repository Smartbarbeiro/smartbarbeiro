<script setup>
import AdminUsersTable from '@/Components/Admin/AdminUsersTable.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    barbershops: {
        type: Object,
        required: true,
    },
    freezeProcessing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['delete', 'freeze']);

const expandedBarbershopIds = ref(new Set());

const isExpanded = (barbershopId) =>
    expandedBarbershopIds.value.has(barbershopId);

const toggleClients = (barbershopId) => {
    const next = new Set(expandedBarbershopIds.value);

    if (next.has(barbershopId)) {
        next.delete(barbershopId);
    } else {
        next.add(barbershopId);
    }

    expandedBarbershopIds.value = next;
};
</script>

<template>
    <div class="d-flex flex-column gap-4">
        <div
            v-if="barbershops.data.length === 0"
            class="app-card p-4 text-center text-secondary small"
        >
            Nenhuma barbearia encontrada.
        </div>

        <div
            v-for="barbershop in barbershops.data"
            :key="barbershop.id"
            class="app-card p-0 overflow-hidden"
        >
            <AdminUsersTable
                :users="[barbershop]"
                nested
                :freeze-processing="freezeProcessing"
                @delete="$emit('delete', $event)"
                @freeze="$emit('freeze', $event)"
            />

            <div class="border-top border-secondary-subtle px-3 py-3">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    :aria-expanded="isExpanded(barbershop.id)"
                    @click="toggleClients(barbershop.id)"
                >
                    <i
                        class="bi me-1"
                        :class="
                            isExpanded(barbershop.id)
                                ? 'bi-chevron-up'
                                : 'bi-chevron-down'
                        "
                        aria-hidden="true"
                    ></i>
                    {{
                        isExpanded(barbershop.id)
                            ? 'Ocultar clientes'
                            : 'Mostrar clientes'
                    }}
                    ({{ barbershop.members.length }})
                </button>

                <div v-show="isExpanded(barbershop.id)" class="mt-3">
                    <AdminUsersTable
                        :users="barbershop.members"
                        nested
                        compact
                        show-joined-at
                        empty-message="Nenhum cliente cadastrado nesta barbearia."
                        :freeze-processing="freezeProcessing"
                        @delete="$emit('delete', $event)"
                        @freeze="$emit('freeze', $event)"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="barbershops.links?.length > 3"
            class="d-flex flex-wrap gap-1"
        >
            <Link
                v-for="(link, index) in barbershops.links"
                :key="index"
                :href="link.url ?? '#'"
                class="btn btn-sm"
                :class="
                    link.active
                        ? 'btn-primary'
                        : link.url
                          ? 'btn-outline-secondary'
                          : 'btn-outline-secondary disabled'
                "
                v-html="link.label"
            />
        </div>
    </div>
</template>
