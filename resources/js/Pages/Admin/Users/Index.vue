<script setup>
import AdminBarbershopGroups from '@/Components/Admin/AdminBarbershopGroups.vue';
import AdminUsersTable from '@/Components/Admin/AdminUsersTable.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    admins: {
        type: Array,
        default: () => [],
    },
    barbershops: {
        type: Object,
        required: true,
    },
    unassignedClients: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    barbershopAccountsCount: {
        type: Number,
        default: 0,
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
</script>

<template>
    <Head title="Painel de controle" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardPageHeader icon="admin" title="Painel de controle" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardAlert
                :show="flashStatus === 'user-deleted'"
                variant="success"
            >
                Usuário excluído. O perfil, assinaturas e armazenamento foram
                removidos.
            </DashboardAlert>

            <DashboardAlert
                :show="flashStatus === 'user-frozen'"
                variant="warning"
            >
                Conta congelada. O usuário não pode entrar nem exibir perfil público.
            </DashboardAlert>

            <DashboardAlert
                :show="flashStatus === 'user-unfrozen'"
                variant="success"
            >
                Conta descongelada.
            </DashboardAlert>

            <DashboardContentCard>
                <div class="d-flex flex-column gap-4">
                    <div>
                        <p class="small text-secondary mb-1">Contas de barbearia</p>
                        <p class="h3 fw-semibold mb-0">{{ barbershopAccountsCount }}</p>
                    </div>

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

                    <div>
                        <h2 class="h6 fw-semibold mb-3">Administradores</h2>
                        <AdminUsersTable
                            :users="admins"
                            empty-message="Nenhum administrador encontrado."
                            :freeze-processing="freezeForm.processing"
                            @delete="confirmDelete"
                            @freeze="toggleFreeze"
                        />
                    </div>

                    <div>
                        <h2 class="h6 fw-semibold mb-3">Barbearias e clientes</h2>
                        <AdminBarbershopGroups
                            :barbershops="barbershops"
                            :freeze-processing="freezeForm.processing"
                            @delete="confirmDelete"
                            @freeze="toggleFreeze"
                        />
                    </div>

                    <div>
                        <h2 class="h6 fw-semibold mb-3">Clientes sem barbearia</h2>
                        <AdminUsersTable
                            :users="unassignedClients.data"
                            :pagination="unassignedClients"
                            empty-message="Nenhum cliente sem barbearia encontrado."
                            :freeze-processing="freezeForm.processing"
                            @delete="confirmDelete"
                            @freeze="toggleFreeze"
                        />
                    </div>
                </div>
            </DashboardContentCard>
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
