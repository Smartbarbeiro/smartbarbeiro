<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const editingId = ref(null);

const createForm = useForm({
    name: '',
    commission_percent: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    commission_percent: '',
    is_active: true,
});

const deleteForm = useForm({});

const statusMessage = computed(() => {
    const status = page.props.flash?.status;

    if (status === 'employee-created') {
        return 'Funcionário adicionado com sucesso.';
    }

    if (status === 'employee-updated') {
        return 'Funcionário atualizado com sucesso.';
    }

    if (status === 'employee-deleted') {
        return 'Funcionário removido com sucesso.';
    }

    return null;
});

const startEdit = (employee) => {
    editingId.value = employee.id;
    editForm.clearErrors();
    editForm.name = employee.name;
    editForm.commission_percent = String(employee.commission_percent);
    editForm.is_active = employee.is_active;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(route('employees.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.is_active = true;
        },
    });
};

const submitEdit = (employeeId) => {
    editForm.patch(route('employees.update', employeeId), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const deleteEmployee = (employee) => {
    if (!window.confirm(`Remover ${employee.name} da equipe?`)) {
        return;
    }

    deleteForm.delete(route('employees.destroy', employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === employee.id) {
                cancelEdit();
            }
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Funcionários" />

        <template #header>
            <DashboardPageHeader icon="users" title="Funcionários" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardAlert
                v-if="statusMessage"
                variant="success"
                :show="true"
            >
                {{ statusMessage }}
            </DashboardAlert>

            <DashboardContentCard
                icon="users"
                title="Equipe da barbearia"
                description="Cadastre os barbeiros que trabalham com você e defina a comissão de cada um sobre os serviços realizados."
            >
                <form class="row g-3 align-items-end" @submit.prevent="submitCreate">
                    <div class="col-md-5">
                        <InputLabel for="employee-name" value="Nome" />
                        <TextInput
                            id="employee-name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 w-100"
                            placeholder="Ex.: Carlos Silva"
                            required
                        />
                        <InputError class="mt-1" :message="createForm.errors.name" />
                    </div>

                    <div class="col-md-3">
                        <InputLabel for="employee-commission" value="Comissão (%)" />
                        <TextInput
                            id="employee-commission"
                            v-model="createForm.commission_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="mt-1 w-100"
                            placeholder="40"
                            required
                        />
                        <InputError class="mt-1" :message="createForm.errors.commission_percent" />
                    </div>

                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input
                                id="employee-active"
                                v-model="createForm.is_active"
                                class="form-check-input"
                                type="checkbox"
                            />
                            <label class="form-check-label" for="employee-active">
                                Ativo
                            </label>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <PrimaryButton
                            type="submit"
                            class="w-100"
                            :disabled="createForm.processing"
                        >
                            Adicionar
                        </PrimaryButton>
                    </div>
                </form>
            </DashboardContentCard>

            <DashboardContentCard
                v-if="employees.length === 0"
                icon="empty"
                title="Nenhum funcionário cadastrado"
                description="Adicione barbeiros para acompanhar comissões e, em breve, atribuir horários na agenda."
                centered
            />

            <DashboardContentCard
                v-else
                icon="clients"
                title="Funcionários cadastrados"
                :description="`${employees.length} ${employees.length === 1 ? 'funcionário' : 'funcionários'} na equipe.`"
            >
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th class="text-end">Comissão</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="employee in employees" :key="employee.id">
                                <template v-if="editingId === employee.id">
                                    <td>
                                        <TextInput
                                            v-model="editForm.name"
                                            type="text"
                                            class="w-100"
                                            required
                                        />
                                        <InputError class="mt-1" :message="editForm.errors.name" />
                                    </td>
                                    <td>
                                        <TextInput
                                            v-model="editForm.commission_percent"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="w-100 text-end"
                                            required
                                        />
                                        <InputError
                                            class="mt-1"
                                            :message="editForm.errors.commission_percent"
                                        />
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-inline-flex justify-content-center">
                                            <input
                                                :id="`edit-active-${employee.id}`"
                                                v-model="editForm.is_active"
                                                class="form-check-input"
                                                type="checkbox"
                                            />
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                :disabled="editForm.processing"
                                                @click="submitEdit(employee.id)"
                                            >
                                                Salvar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="cancelEdit"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </td>
                                </template>

                                <template v-else>
                                    <td class="fw-semibold">{{ employee.name }}</td>
                                    <td class="text-end">
                                        {{ employee.formatted_commission_percent }}
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge"
                                            :class="employee.is_active ? 'bg-success' : 'bg-secondary'"
                                        >
                                            {{ employee.is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                @click="startEdit(employee)"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                @click="deleteEmployee(employee)"
                                            >
                                                Remover
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
