<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SubscribersList from '@/Pages/Profile/Partials/SubscribersList.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    managedUser: {
        type: Object,
        required: true,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
    canFreeze: {
        type: Boolean,
        default: false,
    },
});

const flashStatus = computed(() => usePage().props.flash?.status);

const form = useForm({
    name: props.managedUser.name,
    username: props.managedUser.username,
    email: props.managedUser.email,
    password: '',
    password_confirmation: '',
    is_admin: props.managedUser.is_admin,
    is_frozen: props.managedUser.is_frozen,
});

const confirmingDeletion = ref(false);
const deleteForm = useForm({});

const submit = () => {
    form.patch(route('admin.users.update', props.managedUser.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
        },
    });
};

const deleteUser = () => {
    deleteForm.delete(route('admin.users.destroy', props.managedUser.id));
};

const qrProfileUrl = computed(() => {
    if (!props.managedUser.is_barbershop || !props.managedUser.profile_url) {
        return null;
    }

    const username = form.username || props.managedUser.username;

    try {
        const url = new URL(props.managedUser.profile_url);

        url.pathname = `/barbearias/${username}`;

        return url.toString();
    } catch {
        return props.managedUser.profile_url;
    }
});
</script>

<template>
    <Head :title="`Editar ${managedUser.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">
                <h1 class="h4 mb-0 fw-semibold">Editar usuário</h1>
                <Link
                    :href="route('admin.users.index')"
                    class="link-primary small"
                >
                    Voltar ao painel de controle
                </Link>
            </div>
        </template>

        <div class="d-flex flex-column gap-4">
            <div
                v-if="flashStatus === 'user-updated'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Usuário atualizado.
            </div>

            <div class="app-card p-4">
                <div class="d-flex align-items-start gap-4">
                    <ProfileAvatar
                        :name="managedUser.name"
                        :photo-url="managedUser.profile_photo_url"
                        size="lg"
                    />
                    <div class="small text-secondary">
                        <p class="mb-1">
                            <span class="fw-medium text-body">Tipo:</span>
                            {{
                                managedUser.is_barbershop
                                    ? 'Barbearia'
                                    : 'Cliente'
                            }}
                            <span
                                v-if="managedUser.is_frozen"
                                class="badge bg-danger ms-2"
                            >
                                Congelado
                            </span>
                        </p>
                        <p v-if="managedUser.profile_url" class="mb-1">
                            <span class="fw-medium text-body">Perfil público:</span>
                            <a
                                :href="managedUser.profile_url"
                                class="link-primary ms-1"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ managedUser.profile_url }}
                            </a>
                        </p>
                        <p class="mb-1">
                            <span class="fw-medium text-body">Cadastrado em:</span>
                            {{ managedUser.created_at }}
                        </p>
                        <p v-if="managedUser.is_barbershop" class="mb-1">
                            <span class="fw-medium text-body">Armazenamento:</span>
                            <span class="font-monospace ms-1" style="font-size: 0.75rem">{{
                                managedUser.storage_path
                            }}</span>
                        </p>
                        <p class="mb-0">
                            Assinantes pagos:
                            {{ managedUser.active_subscribers_count }} ativos /
                            {{ managedUser.subscribers_count }} total
                            · Membros da barbearia:
                            {{ managedUser.barbershop_members_count }}
                            · Assinaturas como cliente:
                            {{ managedUser.subscriptions_count }}
                        </p>
                    </div>
                </div>

                <ProfileQrCode
                    v-if="qrProfileUrl"
                    class="mt-4"
                    style="max-width: 28rem"
                    :url="qrProfileUrl"
                    :filename="`${form.username || managedUser.username}-profile`"
                />

                <form @submit.prevent="submit" class="mt-4">
                    <div class="mb-3">
                        <InputLabel for="name" value="Nome" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-100"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div v-if="managedUser.is_barbershop" class="mb-3">
                        <InputLabel for="username" value="Nome da Barbearia" />
                        <TextInput
                            id="username"
                            v-model="form.username"
                            type="text"
                            class="mt-1 w-100"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.username"
                        />
                    </div>

                    <div class="mb-3">
                        <InputLabel for="email" value="E-mail" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 w-100"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mb-3">
                        <InputLabel
                            for="password"
                            value="Nova senha (opcional)"
                        />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 w-100"
                            autocomplete="new-password"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div class="mb-3">
                        <InputLabel
                            for="password_confirmation"
                            value="Confirmar nova senha"
                        />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 w-100"
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="form-check mb-3">
                        <input
                            id="is_admin"
                            v-model="form.is_admin"
                            type="checkbox"
                            class="form-check-input"
                        />
                        <InputLabel
                            for="is_admin"
                            value="Administrador"
                            class="form-check-label"
                        />
                    </div>
                    <p class="form-text mb-0">
                        Administradores não possuem perfil público nem conta de
                        barbearia.
                    </p>
                    <InputError class="mt-2 mb-3" :message="form.errors.is_admin" />

                    <div v-if="canFreeze" class="form-check mb-3">
                        <input
                            id="is_frozen"
                            v-model="form.is_frozen"
                            type="checkbox"
                            class="form-check-input"
                        />
                        <InputLabel
                            for="is_frozen"
                            value="Congelar conta (bloqueia login e perfil público)"
                            class="form-check-label"
                        />
                    </div>
                    <InputError class="mt-2 mb-3" :message="form.errors.is_frozen" />

                    <PrimaryButton :disabled="form.processing">
                        Salvar alterações
                    </PrimaryButton>
                </form>
            </div>

            <div v-if="managedUser.is_barbershop" class="app-card p-4">
                <SubscribersList :subscribers="managedUser.subscribers" />
            </div>

            <div
                v-if="managedUser.barbershop_members.length > 0"
                class="app-card p-4"
            >
                <header class="mb-4">
                    <h2 class="h5 fw-semibold mb-1">Membros da barbearia</h2>
                    <p class="text-secondary small mb-0">
                        Clientes cadastrados nesta barbearia.
                    </p>
                </header>

                <div class="table-responsive">
                    <table class="table table-dark table-hover table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Membro</th>
                                <th scope="col">Cadastrado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in managedUser.barbershop_members"
                                :key="row.id"
                            >
                                <td>
                                    <p class="fw-medium mb-0">
                                        {{ row.member.name }}
                                    </p>
                                    <p class="text-secondary small mb-0">
                                        {{ row.member.email }}
                                    </p>
                                </td>
                                <td class="text-secondary">
                                    {{ row.joined_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="canDelete" class="app-card p-4 app-form-panel">
                <h3 class="h5 fw-semibold">Excluir usuário</h3>
                <p class="text-secondary small mt-1">
                    Remove permanentemente esta conta, perfil público e todo o
                    armazenamento associado.
                </p>
                <DangerButton class="mt-3" @click="confirmingDeletion = true">
                    Excluir usuário
                </DangerButton>
            </div>
        </div>

        <Modal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <div class="p-4">
                <h2 class="h5 fw-semibold">
                    Excluir {{ managedUser.name }}?
                </h2>
                <p class="text-secondary small mt-2 mb-0">
                    Isso não pode ser desfeito. Fotos de perfil, arquivos privados e
                    dados de assinatura serão apagados.
                </p>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <SecondaryButton @click="confirmingDeletion = false">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleteForm.processing"
                        @click="deleteUser"
                    >
                        Excluir permanentemente
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
