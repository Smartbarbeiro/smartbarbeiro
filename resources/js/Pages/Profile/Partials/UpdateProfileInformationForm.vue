<script setup>
import InputError from '@/Components/InputError.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import BarbershopNameInput from '@/Components/BarbershopNameInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import UpdatePasswordForm from './UpdatePasswordForm.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profileUrl: {
        type: String,
        default: null,
    },
    isBarbershop: {
        type: Boolean,
        default: true,
    },
    barbershopMemberships: {
        type: Array,
        default: () => [],
    },
    acrylicQrOrder: {
        type: Object,
        default: null,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
    profile_photo: null,
    remove_profile_photo: false,
});

const photoPreview = ref(user.profile_photo_url);
const photoInput = ref(null);
const showPasswordForm = ref(false);
const usernameLocked = ref(true);
const originalUsername = ref(user.username);

const unlockUsername = () => {
    usernameLocked.value = false;
};

const cancelUsernameChange = () => {
    form.username = originalUsername.value;
    usernameLocked.value = true;
};

const usernameChanged = computed(
    () => form.username !== originalUsername.value,
);

const qrProfileUrl = computed(() => {
    if (!props.isBarbershop || !props.profileUrl) {
        return null;
    }

    const username = form.username || user.username;

    try {
        const url = new URL(props.profileUrl);

        url.pathname = `/barbearias/${username}`;

        return url.toString();
    } catch {
        return props.profileUrl;
    }
});

const onPhotoChange = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    form.profile_photo = file;
    form.remove_profile_photo = false;
    photoPreview.value = URL.createObjectURL(file);
};

const openPhotoPicker = () => {
    photoInput.value?.click();
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('profile.update'), {
        forceFormData: props.isBarbershop,
        preserveScroll: true,
        onSuccess: () => {
            form.profile_photo = null;
            form.remove_profile_photo = false;
            photoPreview.value = usePage().props.auth.user.profile_photo_url;
            originalUsername.value = usePage().props.auth.user.username;
            usernameLocked.value = true;
        },
    });
};
</script>

<template>
    <section>
        <div class="dashboard-section-meta mb-4">
            <template v-if="isBarbershop && profileUrl">
                <p class="text-secondary small mb-0">
                    Perfil público:
                    <Link
                        :href="route('profile.public', { username: user.username })"
                        class="profile-public-link"
                    >
                        {{ profileUrl }}
                    </Link>
                </p>
            </template>

            <div
                v-else-if="barbershopMemberships.length > 0"
                class="mt-0"
            >
                <p class="small fw-medium mb-2">Sua Barbearia Preferida:</p>
                <ul class="list-unstyled mb-0">
                    <li
                        v-for="membership in barbershopMemberships"
                        :key="membership.id"
                        class="small text-secondary mb-1"
                    >
                        <Link
                            v-if="membership.barbershop.username"
                            :href="
                                route('profile.public', {
                                    username: membership.barbershop.username,
                                })
                            "
                            class="link-primary"
                        >
                            {{ membership.barbershop.name }}
                        </Link>
                        <span v-else>{{ membership.barbershop.name }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div v-if="isBarbershop" class="mb-4">
                <InputLabel value="Logo ou foto da barbearia" />

                <div class="d-flex flex-wrap align-items-start gap-3 mt-2">
                    <button
                        type="button"
                        class="profile-barbershop-photo-trigger"
                        aria-label="Escolher logo ou foto da barbearia"
                        @click="openPhotoPicker"
                    >
                        <ProfileAvatar
                            :name="form.name || user.name"
                            :photo-url="photoPreview"
                            size="lg"
                        />
                    </button>

                    <div class="d-flex flex-column gap-2">
                        <input
                            ref="photoInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="form-control form-control-sm"
                            style="max-width: 20rem"
                            @change="onPhotoChange"
                        />

                        <div
                            v-if="qrProfileUrl"
                            class="barbershop-owner-qr-panel barbershop-owner-qr-panel--aside"
                        >
                            <ProfileQrCode
                                :url="qrProfileUrl"
                                :filename="`${form.username || user.username}-profile`"
                                owner-dashboard
                                show-acrylic-order
                                :acrylic-order="acrylicQrOrder"
                            />
                        </div>
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.profile_photo" />
            </div>

            <div class="mb-3">
                <InputLabel for="name" value="Nome" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 w-100"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div v-if="isBarbershop" class="mb-3">
                <div
                    class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1"
                >
                    <InputLabel
                        for="username"
                        value="Nome da Barbearia"
                        class="mb-0"
                    />

                    <SecondaryButton
                        v-if="usernameLocked"
                        type="button"
                        class="btn-sm"
                        @click="unlockUsername"
                    >
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                        Alterar nome
                    </SecondaryButton>

                    <SecondaryButton
                        v-else
                        type="button"
                        class="btn-sm"
                        @click="cancelUsernameChange"
                    >
                        Cancelar alteração
                    </SecondaryButton>
                </div>

                <div
                    v-if="!usernameLocked"
                    class="alert alert-warning small mb-3"
                    role="alert"
                >
                    <strong>Atenção:</strong> alterar o nome da barbearia muda o
                    link público e invalida o QR code atual (digital e físico).
                    Depois de salvar, peça um novo QR code acrílico pelo botão
                    acima e substitua o cartão na sua barbearia.
                </div>

                <div
                    class="mt-1"
                    :class="{
                        'profile-barbershop-name-field-wrap--locked':
                            usernameLocked,
                    }"
                >
                    <BarbershopNameInput
                        id="username"
                        v-model="form.username"
                        required
                        autocomplete="username"
                        :readonly="usernameLocked"
                        :aria-readonly="usernameLocked"
                    />
                </div>

                <p class="form-text">
                    Usado no seu link público: /barbearias/{{ form.username || 'sua-barbearia' }}
                </p>

                <p
                    v-if="usernameChanged"
                    class="form-text text-warning mb-0"
                >
                    O QR code será atualizado somente depois que você salvar.
                </p>

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div class="mb-3">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 w-100"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="mb-3">
                <p class="small mb-2">
                    Seu endereço de e-mail não foi verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="btn btn-link link-primary p-0 align-baseline"
                    >
                        Clique aqui para reenviar o e-mail de verificação.
                    </Link>
                </p>

                <DashboardAlert
                    :show="status === 'verification-link-sent'"
                    variant="success"
                >
                    Um novo link de verificação foi enviado para seu endereço de e-mail.
                </DashboardAlert>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <PrimaryButton :disabled="form.processing">Salvar</PrimaryButton>

                <SecondaryButton
                    type="button"
                    @click="showPasswordForm = !showPasswordForm"
                >
                    <i
                        class="bi me-1"
                        :class="showPasswordForm ? 'bi-eye-slash' : 'bi-key'"
                        aria-hidden="true"
                    ></i>
                    {{ showPasswordForm ? 'Ocultar senha' : 'Atualizar senha' }}
                </SecondaryButton>

                <p
                    v-if="form.recentlySuccessful"
                    class="text-secondary small mb-0"
                >
                    Salvo.
                </p>
            </div>
        </form>

        <UpdatePasswordForm
            v-show="showPasswordForm"
            embedded
            class="profile-password-panel mt-4 pt-4"
        />
    </section>
</template>
