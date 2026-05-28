<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import QRCode from 'qrcode';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'QR code do perfil',
    },
    filename: {
        type: String,
        default: 'profile-qrcode',
    },
    size: {
        type: Number,
        default: 192,
    },
});

const dataUrl = ref('');
const error = ref('');

const generate = async () => {
    if (!props.url) {
        dataUrl.value = '';
        return;
    }

    try {
        error.value = '';
        dataUrl.value = await QRCode.toDataURL(props.url, {
            width: props.size,
            margin: 2,
            errorCorrectionLevel: 'M',
        });
    } catch (e) {
        dataUrl.value = '';
        error.value = 'Não foi possível gerar o QR code.';
    }
};

const download = () => {
    if (!dataUrl.value) {
        return;
    }

    const link = document.createElement('a');
    link.href = dataUrl.value;
    link.download = `${props.filename}.png`;
    link.click();
};

onMounted(generate);

watch(() => props.url, generate);
</script>

<template>
    <div class="app-card p-3">
        <p class="small fw-medium mb-1">{{ label }}</p>
        <p class="font-monospace small text-secondary text-break mb-0">
            {{ url }}
        </p>

        <div class="d-flex flex-wrap align-items-start gap-3 mt-3">
            <div class="border border-secondary-subtle rounded p-2 bg-white">
                <img
                    v-if="dataUrl"
                    :src="dataUrl"
                    :alt="`${label} para ${url}`"
                    class="d-block"
                    :width="size"
                    :height="size"
                />
                <div
                    v-else
                    class="d-flex align-items-center justify-content-center bg-light text-secondary small"
                    :style="{ width: `${size}px`, height: `${size}px` }"
                >
                    {{ error || 'Gerando…' }}
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <SecondaryButton
                    type="button"
                    :disabled="!dataUrl"
                    @click="download"
                >
                    Baixar PNG
                </SecondaryButton>
                <p class="small text-secondary mb-0" style="max-width: 16rem">
                    Escaneie para abrir este perfil no celular.
                </p>
            </div>
        </div>
    </div>
</template>
