<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    photos: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    photo: null,
});

const photoInput = ref(null);
const previewUrl = ref(null);

const selectPhoto = (event) => {
    const file = event.target.files?.[0] ?? null;

    form.photo = file;
    form.clearErrors('photo');

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    form.post(route('haircuts.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (photoInput.value) {
                photoInput.value.value = '';
            }
            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
                previewUrl.value = null;
            }
        },
    });
};

const deletePhoto = (photoId) => {
    if (!window.confirm('Remover esta foto do corte?')) {
        return;
    }

    router.delete(route('haircuts.destroy', photoId), {
        preserveScroll: true,
    });
};

const formatDate = (value) =>
    new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const flashStatus = () => usePage().props.flash?.status;
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Cortes" />

        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Cortes</h1>
        </template>

        <div class="d-flex flex-column gap-4">
            <div
                v-if="flashStatus === 'haircut-photo-uploaded'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Foto do corte enviada com sucesso.
            </div>

            <div
                v-if="flashStatus === 'haircut-photo-deleted'"
                class="alert alert-success mb-0"
                role="alert"
            >
                Foto removida.
            </div>

            <section class="app-card p-4">
                <h2 class="h5 fw-semibold mb-2">Enviar foto do corte</h2>
                <p class="text-secondary small mb-4">
                    Salve fotos dos seus cortes para acompanhar o estilo ao longo
                    do tempo.
                </p>

                <form class="haircut-upload-form" @submit.prevent="submit">
                    <div
                        v-if="previewUrl"
                        class="haircut-upload-preview mb-3"
                    >
                        <img
                            :src="previewUrl"
                            alt="Pré-visualização da foto do corte"
                            class="haircut-upload-preview__image"
                        />
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <label class="btn btn-outline-secondary mb-0">
                            Escolher foto
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/*"
                                class="visually-hidden"
                                @change="selectPhoto"
                            />
                        </label>

                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing || !form.photo"
                        >
                            {{
                                form.processing
                                    ? 'Enviando...'
                                    : 'Enviar foto'
                            }}
                        </PrimaryButton>
                    </div>

                    <InputError class="mt-3 mb-0" :message="form.errors.photo" />
                </form>
            </section>

            <section v-if="photos.length === 0" class="app-card p-4 text-center">
                <p class="text-secondary mb-0">
                    Você ainda não enviou fotos dos seus cortes.
                </p>
            </section>

            <section v-else class="haircut-gallery">
                <div class="row g-3">
                    <div
                        v-for="photo in photos"
                        :key="photo.id"
                        class="col-6 col-md-4 col-lg-3"
                    >
                        <article class="haircut-gallery-card app-card h-100">
                            <img
                                :src="photo.photo_url"
                                :alt="`Foto do corte em ${formatDate(photo.created_at)}`"
                                class="haircut-gallery-card__image"
                            />

                            <div class="haircut-gallery-card__body p-3">
                                <p class="small text-secondary mb-2">
                                    {{ formatDate(photo.created_at) }}
                                </p>

                                <p
                                    v-if="photo.barbershop"
                                    class="small mb-3"
                                >
                                    {{ photo.barbershop.name }}
                                </p>

                                <SecondaryButton
                                    type="button"
                                    class="w-100"
                                    @click="deletePhoto(photo.id)"
                                >
                                    Remover
                                </SecondaryButton>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
