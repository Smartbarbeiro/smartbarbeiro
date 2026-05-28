<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const flashStatus = computed(() => usePage().props.flash?.status);

const searchForm = useForm({
    search: props.filters.search ?? '',
});

const userToDelete = ref(null);
const deleteForm = useForm({});
const freezeForm = useForm({});

const submitSearch = () => {
    searchForm.get(route('admin.users.index'), {
        preserveState: true,
        replace: true,
    });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
};

const closeDeleteModal = () => {
    userToDelete.value = null;
    deleteForm.clearErrors();
};

const deleteUser = () => {
    if (!userToDelete.value) {
        return;
    }

    deleteForm.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const toggleFreeze = (user) => {
    freezeForm.patch(route('admin.users.freeze', user.id), {
        preserveScroll: true,
    });
};

const statusLabel = (user) => {
    if (user.is_frozen) {
        return 'Congelado';
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

    if (user.is_barbershop) {
        return 'badge bg-primary';
    }

    return 'badge bg-secondary';
};
</script>

<template>
    <Head title="Painel de controle" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Painel de controle</h1>
        </template>

        <div class="d-flex flex-column gap-4">
            <div
                v-if="flashStatus === 'user-deleted'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Usuário excluído. O perfil, assinaturas e armazenamento foram
                removidos.
            </div>
            <div
                v-else-if="flashStatus === 'user-frozen'"
                class="alert alert-warning mb-0"
                role="alert"
            >
                Conta congelada. O usuário não pode entrar nem exibir perfil público.
            </div>
            <div
                v-else-if="flashStatus === 'user-unfrozen'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Conta descongelada.
            </div>

            <div class="app-card p-4">
                <form
                    @submit.prevent="submitSearch"
                    class="row g-3 align-items-end"
                >
                    <div class="col-md flex-grow-1">
                        <InputLabel for="search" value="Buscar usuários" />
                        <TextInput
                            id="search"
                            v-model="searchForm.search"
                            type="search"
                            class="mt-1 w-100"
                            placeholder="Nome, usuário ou e-mail"
                        />
                    </div>
                    <div class="col-md-auto">
                        <PrimaryButton :disabled="searchForm.processing">
                            Buscar
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="app-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Usuário</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Assinantes</th>
                                <th scope="col">Membros</th>
                                <th scope="col">Cadastrado em</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                            >
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
                                                    v-if="user.is_admin"
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
                                <td>
                                    <span
                                        class="badge"
                                        :class="statusClass(user)"
                                    >
                                        {{ statusLabel(user) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{
                                        user.active_subscribers_count
                                    }}</span>
                                    <span class="text-secondary">
                                        ativos /
                                        {{ user.subscribers_count }} total
                                    </span>
                                </td>
                                <td>{{ user.barbershop_members_count }}</td>
                                <td class="text-secondary">
                                    {{ user.created_at }}
                                </td>
                                <td>
                                    <div
                                        class="d-flex flex-wrap justify-content-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route('admin.users.edit', user.id)
                                            "
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
                                            :disabled="freezeForm.processing"
                                            @click="toggleFreeze(user)"
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
                                            @click="confirmDelete(user)"
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
                    v-if="users.data.length === 0"
                    class="p-4 text-center text-secondary small"
                >
                    Nenhum usuário encontrado.
                </div>

                <div
                    v-if="users.links?.length > 3"
                    class="d-flex flex-wrap gap-1 border-top border-secondary-subtle p-3"
                >
                    <Link
                        v-for="(link, index) in users.links"
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
        </div>

        <Modal :show="!!userToDelete" @close="closeDeleteModal">
            <div class="p-4">
                <h2 class="h5 fw-semibold">
                    Excluir {{ userToDelete?.name }}?
                </h2>
                <p class="text-secondary small mt-2 mb-0">
                    Isso remove permanentemente a conta, perfil público, plano de
                    assinatura, registros de assinatura do Mercado Pago, foto de
                    perfil e pasta de armazenamento privada.
                </p>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <SecondaryButton @click="closeDeleteModal">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleteForm.processing"
                        @click="deleteUser"
                    >
                        Excluir usuário
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
