<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
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

const removePhoto = () => {
    form.profile_photo = null;
    form.remove_profile_photo = true;
    photoPreview.value = null;

    if (photoInput.value) {
        photoInput.value.value = '';
    }
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
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Informações do perfil</h2>

            <p class="text-secondary small mb-0">
                <template v-if="isBarbershop">
                    Atualize as informações do perfil, nome da barbearia e
                    endereço de e-mail da sua conta.
                </template>
                <template v-else>
                    Atualize seu nome e endereço de e-mail.
                </template>
            </p>

            <template v-if="isBarbershop && profileUrl">
                <p class="text-secondary small mt-3 mb-0">
                    Perfil público:
                    <Link
                        :href="route('profile.public', { username: user.username })"
                        class="link-primary fw-medium"
                    >
                        {{ profileUrl }}
                    </Link>
                </p>
            </template>

            <div
                v-else-if="barbershopMemberships.length > 0"
                class="mt-3"
            >
                <p class="small fw-medium mb-2">Suas barbearias</p>
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
        </header>

        <form @submit.prevent="submit" class="mt-4">
            <div v-if="isBarbershop" class="mb-4">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-6">
                        <InputLabel value="Foto de perfil" />

                        <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                            <ProfileAvatar
                                :name="form.name || user.name"
                                :photo-url="photoPreview"
                                size="lg"
                            />

                            <div class="d-flex flex-column gap-2">
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="form-control form-control-sm"
                                    style="max-width: 20rem"
                                    @change="onPhotoChange"
                                />
                                <SecondaryButton
                                    v-if="photoPreview"
                                    type="button"
                                    @click="removePhoto"
                                >
                                    Remover foto
                                </SecondaryButton>
                            </div>
                        </div>

                        <InputError class="mt-2" :message="form.errors.profile_photo" />
                    </div>

                    <div v-if="qrProfileUrl" class="col-lg-6">
                        <ProfileQrCode
                            :url="qrProfileUrl"
                            :filename="`${form.username || user.username}-profile`"
                            show-acrylic-order
                            :acrylic-order="acrylicQrOrder"
                        />
                    </div>
                </div>
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
                <InputLabel for="username" value="Nome da Barbearia" />

                <TextInput
                    id="username"
                    type="text"
                    class="mt-1 w-100"
                    v-model="form.username"
                    required
                    autocomplete="username"
                />

                <p class="form-text">
                    Usado no seu link público: /barbearias/{{ form.username || 'sua-barbearia' }}
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

                <div
                    v-show="status === 'verification-link-sent'"
                    class="alert alert-success py-2 small mb-0"
                    role="alert"
                >
                    Um novo link de verificação foi enviado para seu endereço de e-mail.
                </div>
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
