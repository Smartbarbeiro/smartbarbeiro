<script setup>
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    users: {
        type: Array,
        required: true,
    },
    pagination: {
        type: Object,
        default: null,
    },
    emptyMessage: {
        type: String,
        default: 'Nenhum usuário encontrado.',
    },
    showAdminBadge: {
        type: Boolean,
        default: false,
    },
    freezeProcessing: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    nested: {
        type: Boolean,
        default: false,
    },
    showJoinedAt: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['delete', 'freeze']);

const statusLabel = (user) => {
    if (user.is_frozen) {
        return 'Congelado';
    }

    if (user.is_admin) {
        return 'Admin';
    }

    if (user.is_barbershop) {
        return 'Barbearia';
    }

    return 'Cliente';
};

const statusClass = (user) => {
    if (user.is_frozen) {
        return 'badge bg-danger';
    }

    if (user.is_admin) {
        return 'badge bg-info text-dark';
    }

    if (user.is_barbershop) {
        return 'badge bg-primary';
    }

    return 'badge bg-secondary';
};
</script>

<template>
    <div :class="nested ? '' : 'app-card p-0 overflow-hidden'">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-dark-custom mb-0">
                <thead>
                    <tr>
                        <th scope="col">Usuário</th>
                        <th v-if="!compact" scope="col">Tipo</th>
                        <th v-if="!compact" scope="col">Assinantes</th>
                        <th v-if="!compact" scope="col">Membros</th>
                        <th scope="col">
                            {{ showJoinedAt ? 'Cadastro na barbearia' : 'Cadastrado em' }}
                        </th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <ProfileAvatar
                                    :name="user.name"
                                    :photo-url="user.profile_photo_url"
                                    size="sm"
                                />
                                <div>
                                    <p class="fw-medium mb-0">
                                        {{ user.name }}
                                        <span
                                            v-if="showAdminBadge && user.is_admin"
                                            class="badge bg-primary ms-1"
                                        >
                                            Admin
                                        </span>
                                    </p>
                                    <p class="text-secondary small mb-0">
                                        {{ user.email }}
                                    </p>
                                    <p
                                        v-if="user.username"
                                        class="text-secondary mb-0"
                                        style="font-size: 0.75rem"
                                    >
                                        /barbearias/{{ user.username }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td v-if="!compact">
                            <span class="badge" :class="statusClass(user)">
                                {{ statusLabel(user) }}
                            </span>
                        </td>
                        <td v-if="!compact">
                            <span class="fw-medium">{{
                                user.active_subscribers_count
                            }}</span>
                            <span class="text-secondary">
                                ativos / {{ user.subscribers_count }} total
                            </span>
                        </td>
                        <td v-if="!compact">
                            {{ user.barbershop_members_count }}
                        </td>
                        <td class="text-secondary">
                            {{ showJoinedAt ? user.joined_at : user.created_at }}
                        </td>
                        <td>
                            <div
                                class="d-flex flex-wrap justify-content-end gap-2"
                            >
                                <Link
                                    :href="route('admin.users.edit', user.id)"
                                    class="btn btn-outline-secondary btn-sm"
                                >
                                    Editar
                                </Link>
                                <button
                                    v-if="user.can_freeze"
                                    type="button"
                                    class="btn btn-sm"
                                    :class="
                                        user.is_frozen
                                            ? 'btn-outline-success'
                                            : 'btn-outline-warning'
                                    "
                                    :disabled="freezeProcessing"
                                    @click="$emit('freeze', user)"
                                >
                                    {{
                                        user.is_frozen
                                            ? 'Descongelar'
                                            : 'Congelar'
                                    }}
                                </button>
                                <button
                                    v-if="user.can_delete"
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    @click="$emit('delete', user)"
                                >
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="users.length === 0"
            class="p-4 text-center text-secondary small"
        >
            {{ emptyMessage }}
        </div>

        <div
            v-if="pagination?.links?.length > 3"
            class="d-flex flex-wrap gap-1 border-top border-secondary-subtle p-3"
        >
            <Link
                v-for="(link, index) in pagination.links"
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
