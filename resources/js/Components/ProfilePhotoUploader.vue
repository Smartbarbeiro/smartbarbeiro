<script setup>
import Modal from '@/Components/Modal.vue';
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
const zoomRatio = ref(1);

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

const resetCropModal = () => {
    showCropModal.value = false;
    cropError.value = null;
    destroyCropper();
    revokePendingUrl();
    cropImageSrc.value = null;
    zoomRatio.value = 1;
};

const cancelCropModal = () => {
    resetCropModal();
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

const syncZoomRatio = () => {
    if (!cropper) {
        return;
    }

    const imageData = cropper.getImageData();
    zoomRatio.value = Number((imageData?.ratio ?? 1).toFixed(2));
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
        ready() {
            syncZoomRatio();
        },
        zoom() {
            syncZoomRatio();
        },
    });
};

watch(showCropModal, (visible) => {
    if (visible) {
        initCropper();
    }
});

const zoomIn = () => {
    cropper?.zoom(0.12);
};

const zoomOut = () => {
    cropper?.zoom(-0.12);
};

const onZoomSlider = (event) => {
    const value = Number(event.target.value);

    if (!cropper || Number.isNaN(value)) {
        return;
    }

    cropper.zoomTo(value);
};

const confirmCrop = async () => {
    if (!cropper || isPreparingCrop.value) {
        return false;
    }

    isPreparingCrop.value = true;
    cropError.value = null;

    try {
        const blob = await exportCircularCrop(cropper);
        const file = blobToJpegFile(blob);
        emit('selected', file);
        resetCropModal();

        return true;
    } catch (error) {
        cropError.value =
            error instanceof Error
                ? error.message
                : 'Não foi possível recortar a imagem.';

        return false;
    } finally {
        isPreparingCrop.value = false;
    }
};

const onModalClose = async () => {
    if (isPreparingCrop.value) {
        return;
    }

    if (!cropper) {
        cancelCropModal();

        return;
    }

    await confirmCrop();
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

        <Modal :show="showCropModal" max-width="lg" @close="onModalClose">
            <div class="modal-header border-secondary">
                <h2 class="modal-title h5 mb-0">Ajustar logo ou foto</h2>
            </div>

            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Arraste para posicionar e use os controles de zoom. Clique
                    fora do modal ou pressione Esc para salvar em formato
                    circular.
                </p>

                <div class="profile-photo-cropper">
                    <img
                        ref="imageRef"
                        :src="cropImageSrc ?? undefined"
                        alt="Pré-visualização para recorte"
                        class="profile-photo-cropper__image"
                    />
                </div>

                <div class="profile-photo-cropper__zoom mt-3">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary profile-photo-cropper__zoom-btn"
                        aria-label="Diminuir zoom"
                        :disabled="isPreparingCrop"
                        @click="zoomOut"
                    >
                        <i class="bi bi-dash-lg" aria-hidden="true"></i>
                    </button>

                    <input
                        type="range"
                        class="form-range profile-photo-cropper__zoom-slider"
                        min="0.2"
                        max="3"
                        step="0.01"
                        :value="zoomRatio"
                        :disabled="isPreparingCrop"
                        aria-label="Zoom da imagem"
                        @input="onZoomSlider"
                    />

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary profile-photo-cropper__zoom-btn"
                        aria-label="Aumentar zoom"
                        :disabled="isPreparingCrop"
                        @click="zoomIn"
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <p
                    v-if="isPreparingCrop"
                    class="text-secondary small mb-0 mt-3"
                >
                    Salvando foto...
                </p>

                <p v-if="cropError" class="text-danger small mb-0 mt-3">
                    {{ cropError }}
                </p>
            </div>

            <div class="modal-footer border-secondary">
                <SecondaryButton type="button" @click="cancelCropModal">
                    Cancelar
                </SecondaryButton>
            </div>
        </Modal>
    </div>
</template>
