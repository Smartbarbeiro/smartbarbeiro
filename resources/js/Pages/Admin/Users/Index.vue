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
</script>

<template>
    <Head title="User control panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                User control panel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="flashStatus === 'user-deleted'"
                    class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
                >
                    User deleted. Their profile, subscriptions, and storage were
                    removed.
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <form
                        @submit.prevent="submitSearch"
                        class="flex flex-wrap items-end gap-4"
                    >
                        <div class="min-w-[16rem] flex-1">
                            <InputLabel for="search" value="Search users" />
                            <TextInput
                                id="search"
                                v-model="searchForm.search"
                                type="search"
                                class="mt-1 block w-full"
                                placeholder="Name, username, or email"
                            />
                        </div>
                        <PrimaryButton :disabled="searchForm.processing">
                            Search
                        </PrimaryButton>
                    </form>
                </div>

                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-gray-600"
                                    >
                                        User
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-gray-600"
                                    >
                                        Email
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-gray-600"
                                    >
                                        Joined
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium text-gray-600"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <ProfileAvatar
                                                :name="user.name"
                                                :photo-url="user.profile_photo_url"
                                                size="sm"
                                            />
                                            <div>
                                                <p class="font-medium text-gray-900">
                                                    {{ user.name }}
                                                    <span
                                                        v-if="user.is_admin"
                                                        class="ms-1 rounded bg-indigo-100 px-1.5 py-0.5 text-xs font-medium text-indigo-700"
                                                    >
                                                        Admin
                                                    </span>
                                                </p>
                                                <p class="text-gray-500">
                                                    @{{ user.username }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ user.created_at }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex justify-end gap-2"
                                        >
                                            <Link
                                                :href="
                                                    route('admin.users.edit', user.id)
                                                "
                                                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                v-if="user.can_delete"
                                                type="button"
                                                class="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
                                                @click="confirmDelete(user)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users.data.length === 0"
                        class="p-8 text-center text-sm text-gray-500"
                    >
                        No users found.
                    </div>

                    <div
                        v-if="users.links?.length > 3"
                        class="flex flex-wrap gap-1 border-t border-gray-200 px-4 py-3"
                    >
                        <Link
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url ?? '#'"
                            class="rounded px-3 py-1 text-sm"
                            :class="
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                      ? 'text-gray-700 hover:bg-gray-100'
                                      : 'cursor-not-allowed text-gray-400'
                            "
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="!!userToDelete" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ userToDelete?.name }}?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    This permanently removes their account, public profile,
                    subscription plan, Mercado Pago subscription records, profile
                    photo, and private storage folder.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleteForm.processing"
                        @click="deleteUser"
                    >
                        Delete user
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
