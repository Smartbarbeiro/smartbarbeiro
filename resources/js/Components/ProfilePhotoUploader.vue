<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { blobToJpegFile, exportCircularCrop } from '@/utils/profilePhotoCrop';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    photoUrl: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'lg',
    },
    highlighted: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['selected']);

const fileInput = ref(null);
const imageRef = ref(null);
const showCropModal = ref(false);
const cropImageSrc = ref(null);
const cropError = ref(null);
const isPreparingCrop = ref(false);

let cropper = null;
let pendingObjectUrl = null;

const revokePendingUrl = () => {
    if (pendingObjectUrl) {
        URL.revokeObjectURL(pendingObjectUrl);
        pendingObjectUrl = null;
    }
};

const destroyCropper = () => {
    cropper?.destroy();
    cropper = null;
};

const closeCropModal = () => {
    showCropModal.value = false;
    cropError.value = null;
    destroyCropper();
    revokePendingUrl();
    cropImageSrc.value = null;
};

const openFilePicker = () => {
    if (props.disabled) {
        return;
    }

    fileInput.value?.click();
};

const onFileSelected = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        cropError.value = 'Selecione um arquivo de imagem válido.';

        return;
    }

    revokePendingUrl();
    pendingObjectUrl = URL.createObjectURL(file);
    cropImageSrc.value = pendingObjectUrl;
    showCropModal.value = true;
};

const initCropper = async () => {
    await nextTick();

    if (!imageRef.value || !cropImageSrc.value) {
        return;
    }

    destroyCropper();

    cropper = new Cropper(imageRef.value, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        restore: false,
        guides: false,
        center: true,
        highlight: false,
        background: false,
        movable: true,
        zoomable: true,
        scalable: false,
        rotatable: false,
        cropBoxMovable: false,
        cropBoxResizable: false,
        toggleDragModeOnDblclick: false,
    });
};

watch(showCropModal, (visible) => {
    if (visible) {
        initCropper();
    }
});

const confirmCrop = async () => {
    if (!cropper || isPreparingCrop.value) {
        return;
    }

    isPreparingCrop.value = true;
    cropError.value = null;

    try {
        const blob = await exportCircularCrop(cropper);
        const file = blobToJpegFile(blob);
        emit('selected', file);
        closeCropModal();
    } catch (error) {
        cropError.value =
            error instanceof Error
                ? error.message
                : 'Não foi possível recortar a imagem.';
    } finally {
        isPreparingCrop.value = false;
    }
};

onBeforeUnmount(() => {
    destroyCropper();
    revokePendingUrl();
});
</script>

<template>
    <div class="profile-photo-uploader">
        <button
            type="button"
            class="profile-photo-uploader__trigger"
            :class="{ 'profile-photo-uploader__trigger--highlighted': highlighted }"
            :disabled="disabled"
            aria-label="Trocar foto de perfil"
            @click="openFilePicker"
        >
            <ProfileAvatar
                :name="name"
                :photo-url="photoUrl"
                :size="size"
            />

            <span class="profile-photo-uploader__overlay" aria-hidden="true">
                <i class="bi bi-upload profile-photo-uploader__icon"></i>
                <span class="profile-photo-uploader__label">Trocar Foto</span>
            </span>
        </button>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="visually-hidden"
            @change="onFileSelected"
        />

        <Modal :show="showCropModal" max-width="lg" @close="closeCropModal">
            <div class="modal-header border-secondary">
                <h2 class="modal-title h5 mb-0">Ajustar logo ou foto</h2>
            </div>

            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Arraste para posicionar e use o zoom para enquadrar. A foto
                    será salva em formato circular.
                </p>

                <div class="profile-photo-cropper">
                    <img
                        ref="imageRef"
                        :src="cropImageSrc ?? undefined"
                        alt="Pré-visualização para recorte"
                        class="profile-photo-cropper__image"
                    />
                </div>

                <p v-if="cropError" class="text-danger small mb-0 mt-3">
                    {{ cropError }}
                </p>
            </div>

            <div class="modal-footer border-secondary">
                <SecondaryButton type="button" @click="closeCropModal">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    :disabled="isPreparingCrop"
                    @click="confirmCrop"
                >
                    Usar esta foto
                </PrimaryButton>
            </div>
        </Modal>
    </div>
</template>
