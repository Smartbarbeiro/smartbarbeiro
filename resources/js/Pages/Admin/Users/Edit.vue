<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
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
});

const flashStatus = computed(() => usePage().props.flash?.status);

const form = useForm({
    name: props.managedUser.name,
    username: props.managedUser.username,
    email: props.managedUser.email,
    password: '',
    password_confirmation: '',
    is_admin: props.managedUser.is_admin,
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
    <Head :title="`Edit ${managedUser.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit user
                </h2>
                <Link
                    :href="route('admin.users.index')"
                    class="text-sm text-indigo-600 underline hover:text-indigo-500"
                >
                    Back to users
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="flashStatus === 'user-updated'"
                    class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
                >
                    User updated.
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <div class="flex items-start gap-4">
                        <ProfileAvatar
                            :name="managedUser.name"
                            :photo-url="managedUser.profile_photo_url"
                            size="lg"
                        />
                        <div class="text-sm text-gray-600">
                            <p>
                                <span class="font-medium text-gray-900"
                                    >Public profile:</span
                                >
                                <a
                                    :href="managedUser.profile_url"
                                    class="ms-1 text-indigo-600 underline"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {{ managedUser.profile_url }}
                                </a>
                            </p>
                            <p class="mt-1">
                                <span class="font-medium text-gray-900"
                                    >Joined:</span
                                >
                                {{ managedUser.created_at }}
                            </p>
                            <p class="mt-1">
                                <span class="font-medium text-gray-900"
                                    >Storage:</span
                                >
                                <span class="font-mono text-xs">{{
                                    managedUser.storage_path
                                }}</span>
                            </p>
                            <p class="mt-1">
                                Subscribers: {{ managedUser.subscribers_count }}
                                · Subscriptions:
                                {{ managedUser.subscriptions_count }}
                                · Paid plan:
                                {{
                                    managedUser.has_subscription_plan
                                        ? 'Yes'
                                        : 'No'
                                }}
                            </p>
                        </div>
                    </div>

                    <ProfileQrCode
                        class="mt-6 max-w-md"
                        :url="qrProfileUrl"
                        :filename="`${form.username || managedUser.username}-profile`"
                    />

                    <form @submit.prevent="submit" class="mt-8 space-y-6">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="username" value="Username" />
                            <TextInput
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.username"
                            />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel
                                for="password"
                                value="New password (optional)"
                            />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.password"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="password_confirmation"
                                value="Confirm new password"
                            />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="is_admin"
                                v-model="form.is_admin"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <InputLabel for="is_admin" value="Administrator" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.is_admin" />

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Save changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div
                    v-if="canDelete"
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <h3 class="text-lg font-medium text-gray-900">
                        Delete user
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Permanently remove this account, their public profile,
                        and all associated storage.
                    </p>
                    <DangerButton class="mt-4" @click="confirmingDeletion = true">
                        Delete user
                    </DangerButton>
                </div>
            </div>
        </div>

        <Modal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ managedUser.name }}?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    This cannot be undone. Profile photos, private files, and
                    subscription data will be erased.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingDeletion = false">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleteForm.processing"
                        @click="deleteUser"
                    >
                        Delete permanently
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
