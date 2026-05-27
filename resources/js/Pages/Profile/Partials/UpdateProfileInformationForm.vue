<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                <template v-if="isBarbershop">
                    Update your account's profile information, public username, and
                    email address.
                </template>
                <template v-else>
                    Update your account name and email address.
                </template>
            </p>

            <template v-if="isBarbershop && profileUrl">
                <p class="mt-3 text-sm text-gray-600">
                    Public profile:
                    <Link
                        :href="route('profile.public', { username: user.username })"
                        class="font-medium text-indigo-600 underline hover:text-indigo-500"
                    >
                        {{ profileUrl }}
                    </Link>
                </p>

                <ProfileQrCode
                    v-if="qrProfileUrl"
                    class="mt-4 max-w-md"
                    :url="qrProfileUrl"
                    :filename="`${form.username || user.username}-profile`"
                />
            </template>

            <div
                v-else-if="barbershopMemberships.length > 0"
                class="mt-3"
            >
                <p class="text-sm font-medium text-gray-700">
                    Your barbershops
                </p>
                <ul class="mt-2 space-y-1">
                    <li
                        v-for="membership in barbershopMemberships"
                        :key="membership.id"
                        class="text-sm text-gray-600"
                    >
                        <Link
                            v-if="membership.barbershop.username"
                            :href="
                                route('profile.public', {
                                    username: membership.barbershop.username,
                                })
                            "
                            class="text-indigo-600 underline hover:text-indigo-500"
                        >
                            {{ membership.barbershop.name }}
                        </Link>
                        <span v-else>{{ membership.barbershop.name }}</span>
                    </li>
                </ul>
            </div>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div v-if="isBarbershop">
                <InputLabel value="Profile photo" />

                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <ProfileAvatar
                        :name="form.name || user.name"
                        :photo-url="photoPreview"
                        size="lg"
                    />

                    <div class="flex flex-col gap-2">
                        <input
                            ref="photoInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full max-w-xs text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="onPhotoChange"
                        />
                        <p class="text-xs text-gray-500">
                            JPG, PNG or WebP. Max 2 MB.
                        </p>
                        <SecondaryButton
                            v-if="photoPreview"
                            type="button"
                            @click="removePhoto"
                        >
                            Remove photo
                        </SecondaryButton>
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.profile_photo" />
            </div>

            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div v-if="isBarbershop">
                <InputLabel for="username" value="Username" />

                <TextInput
                    id="username"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.username"
                    required
                    autocomplete="username"
                />

                <p class="mt-1 text-xs text-gray-500">
                    Used in your public link: /barbearias/{{ form.username || 'username' }}
                </p>

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
