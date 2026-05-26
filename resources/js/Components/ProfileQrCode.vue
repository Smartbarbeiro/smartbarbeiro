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
        default: 'Profile QR code',
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
        error.value = 'Could not generate QR code.';
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
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm font-medium text-gray-900">{{ label }}</p>
        <p class="mt-1 break-all font-mono text-xs text-gray-500">
            {{ url }}
        </p>

        <div class="mt-4 flex flex-wrap items-start gap-4">
            <div
                class="rounded-md border border-white bg-white p-2 shadow-sm"
            >
                <img
                    v-if="dataUrl"
                    :src="dataUrl"
                    :alt="`${label} for ${url}`"
                    class="block"
                    :width="size"
                    :height="size"
                />
                <div
                    v-else
                    class="flex items-center justify-center bg-gray-100 text-xs text-gray-500"
                    :style="{ width: `${size}px`, height: `${size}px` }"
                >
                    {{ error || 'Generating…' }}
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <SecondaryButton
                    type="button"
                    :disabled="!dataUrl"
                    @click="download"
                >
                    Download PNG
                </SecondaryButton>
                <p class="max-w-xs text-xs text-gray-500">
                    Scan to open this profile on a phone.
                </p>
            </div>
        </div>
    </div>
</template>
