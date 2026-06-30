<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
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

const galleryInput = ref(null);
const cameraInput = ref(null);
const previewUrl = ref(null);

const resetPhotoInputs = () => {
    if (galleryInput.value) {
        galleryInput.value.value = '';
    }

    if (cameraInput.value) {
        cameraInput.value.value = '';
    }
};

const selectPhoto = (event) => {
    const file = event.target.files?.[0] ?? null;

    form.photo = file;
    form.clearErrors('photo');

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const openCamera = () => {
    cameraInput.value?.click();
};

const submit = () => {
    form.post(route('haircuts.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            resetPhotoInputs();
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
            <DashboardPageHeader icon="haircuts" title="Cortes" />
        </template>

        <div class="d-flex flex-column gap-4">
            <DashboardAlert
                :show="flashStatus() === 'haircut-photo-uploaded'"
                variant="success"
            >
                Foto do corte enviada com sucesso.
            </DashboardAlert>

            <DashboardAlert
                :show="flashStatus() === 'haircut-photo-deleted'"
                variant="success"
            >
                Foto removida.
            </DashboardAlert>

            <DashboardContentCard
                icon="upload"
                title="Enviar foto do corte"
                description="Salve fotos dos seus cortes para acompanhar o estilo ao longo do tempo."
            >
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
                            <i class="bi bi-images me-2" aria-hidden="true"></i>
                            Escolher foto
                            <input
                                ref="galleryInput"
                                type="file"
                                accept="image/*"
                                class="visually-hidden"
                                @change="selectPhoto"
                            />
                        </label>

                        <button
                            type="button"
                            class="btn btn-outline-secondary mb-0"
                            @click="openCamera"
                        >
                            <i class="bi bi-camera me-2" aria-hidden="true"></i>
                            Tirar foto
                        </button>

                        <input
                            ref="cameraInput"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            class="visually-hidden"
                            @change="selectPhoto"
                        />

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
            </DashboardContentCard>

            <DashboardContentCard
                v-if="photos.length === 0"
                icon="gallery"
                title="Galeria de cortes"
                description="Suas fotos aparecerão aqui depois do primeiro envio."
                centered
            >
                <p class="text-secondary mb-0">
                    Você ainda não enviou fotos dos seus cortes.
                </p>
            </DashboardContentCard>

            <DashboardContentCard
                v-else
                icon="gallery"
                title="Galeria de cortes"
                description="Histórico visual dos seus estilos anteriores."
            >
                <section class="haircut-gallery">
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
            </DashboardContentCard>
        </div>
    </AuthenticatedLayout>
</template>
