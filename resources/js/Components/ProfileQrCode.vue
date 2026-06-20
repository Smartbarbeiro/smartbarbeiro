<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import QRCode from 'qrcode';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

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
    showAcrylicOrder: {
        type: Boolean,
        default: false,
    },
    acrylicOrder: {
        type: Object,
        default: null,
    },
    defaultExpanded: {
        type: Boolean,
        default: false,
    },
    hideShareButton: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const dataUrl = ref('');
const error = ref('');
const showOrderModal = ref(false);
const isExpanded = ref(props.defaultExpanded);
const cepLookupLoading = ref(false);
const cepLookupError = ref('');
const numberInput = ref(null);
const lastLookedUpCep = ref('');

const orderForm = useForm({
    recipient_name: user.value?.name ?? '',
    phone: '',
    postal_code: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
});

const acrylicStatusClass = (status) => {
    if (status === 'pending') {
        return 'badge bg-secondary';
    }

    if (status === 'printed') {
        return 'badge bg-warning text-dark';
    }

    return 'badge bg-success';
};

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

const openOrderModal = () => {
    orderForm.reset();
    orderForm.recipient_name = user.value?.name ?? '';
    cepLookupError.value = '';
    lastLookedUpCep.value = '';
    showOrderModal.value = true;
};

const closeOrderModal = () => {
    showOrderModal.value = false;
    orderForm.clearErrors();
    cepLookupError.value = '';
    lastLookedUpCep.value = '';
};

const normalizePostalCode = (value) => (value ?? '').replace(/\D/g, '').slice(0, 8);

const formatPostalCode = (value) => {
    const digits = normalizePostalCode(value);

    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
};

const lookupPostalCode = async () => {
    const digits = normalizePostalCode(orderForm.postal_code);

    if (digits.length !== 8 || digits === lastLookedUpCep.value) {
        return;
    }

    cepLookupLoading.value = true;
    cepLookupError.value = '';

    try {
        const response = await fetch(
            route('cep.lookup', { postalCode: digits }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            cepLookupError.value =
                data.message ?? 'CEP não encontrado. Verifique e tente novamente.';
            return;
        }

        lastLookedUpCep.value = digits;
        orderForm.postal_code = data.postal_code ?? formatPostalCode(digits);
        orderForm.street = data.street ?? '';
        orderForm.neighborhood = data.neighborhood ?? '';
        orderForm.city = data.city ?? '';
        orderForm.state = data.state ?? '';

        numberInput.value?.focus();
    } catch {
        cepLookupError.value =
            'Não foi possível consultar o CEP. Tente novamente.';
    } finally {
        cepLookupLoading.value = false;
    }
};

const submitOrder = () => {
    orderForm.post(route('profile.acrylic-qr-orders.store'), {
        preserveScroll: true,
        onSuccess: () => closeOrderModal(),
    });
};

onMounted(generate);

watch(() => props.url, generate);

watch(
    () => orderForm.postal_code,
    (value) => {
        const formatted = formatPostalCode(value);

        if (formatted !== value) {
            orderForm.postal_code = formatted;
            return;
        }

        const digits = normalizePostalCode(formatted);

        if (digits.length === 8 && digits !== lastLookedUpCep.value) {
            lookupPostalCode();
        }
    },
);
</script>

<template>
    <div class="profile-qr-section">
        <SecondaryButton
            v-if="!hideShareButton"
            type="button"
            :aria-expanded="isExpanded"
            @click="isExpanded = !isExpanded"
        >
            <i class="bi bi-qr-code me-2"></i>
            Compartilhar Qrcode
        </SecondaryButton>

        <div v-show="isExpanded" class="app-card p-3 mt-3">
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

                <SecondaryButton
                    v-if="showAcrylicOrder && !acrylicOrder"
                    type="button"
                    @click="openOrderModal"
                >
                    Pedir QR acrílico físico
                </SecondaryButton>

                <div
                    v-else-if="showAcrylicOrder && acrylicOrder"
                    class="small"
                    style="max-width: 16rem"
                >
                    <span
                        class="badge"
                        :class="acrylicStatusClass(acrylicOrder.status)"
                    >
                        {{ acrylicOrder.status_label }}
                    </span>
                    <p class="text-secondary mb-0 mt-2">
                        Pedido em {{ new Date(acrylicOrder.created_at).toLocaleDateString('pt-BR') }}.
                    </p>
                </div>

                <p class="small text-secondary mb-0" style="max-width: 16rem">
                    Escaneie para abrir este perfil no celular.
                </p>
            </div>
            </div>
        </div>

        <Modal :show="showOrderModal" max-width="lg" @close="closeOrderModal">
            <form @submit.prevent="submitOrder">
                <div class="modal-header border-secondary">
                    <h2 class="modal-title h5">Pedir QR acrílico físico</h2>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        aria-label="Fechar"
                        @click="closeOrderModal"
                    />
                </div>
                <div class="modal-body">
                    <p class="small text-secondary">
                        Informe o endereço de entrega. Nossa equipe produzirá
                        seu QR code em acrílico e enviará pelos Correios.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <InputLabel for="recipient_name" value="Nome do destinatário" />
                            <TextInput
                                id="recipient_name"
                                v-model="orderForm.recipient_name"
                                class="mt-1 w-100"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.recipient_name" />
                        </div>
                        <div class="col-md-6">
                            <InputLabel for="phone" value="Telefone" />
                            <TextInput
                                id="phone"
                                v-model="orderForm.phone"
                                class="mt-1 w-100"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.phone" />
                        </div>
                        <div class="col-md-4">
                            <InputLabel for="postal_code" value="CEP" />
                            <TextInput
                                id="postal_code"
                                v-model="orderForm.postal_code"
                                class="mt-1 w-100"
                                inputmode="numeric"
                                autocomplete="postal-code"
                                maxlength="9"
                                placeholder="00000-000"
                                required
                                @blur="lookupPostalCode"
                            />
                            <p
                                v-if="cepLookupLoading"
                                class="small text-secondary mb-0 mt-2"
                            >
                                Buscando endereço...
                            </p>
                            <p
                                v-else-if="cepLookupError"
                                class="small text-danger mb-0 mt-2"
                                role="alert"
                            >
                                {{ cepLookupError }}
                            </p>
                            <InputError class="mt-2" :message="orderForm.errors.postal_code" />
                        </div>
                        <div class="col-md-8">
                            <InputLabel for="street" value="Rua" />
                            <TextInput
                                id="street"
                                v-model="orderForm.street"
                                class="mt-1 w-100"
                                autocomplete="address-line1"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.street" />
                        </div>
                        <div class="col-md-3">
                            <InputLabel for="number" value="Número" />
                            <TextInput
                                id="number"
                                ref="numberInput"
                                v-model="orderForm.number"
                                class="mt-1 w-100"
                                autocomplete="off"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.number" />
                        </div>
                        <div class="col-md-5">
                            <InputLabel for="complement" value="Complemento" />
                            <TextInput
                                id="complement"
                                v-model="orderForm.complement"
                                class="mt-1 w-100"
                                autocomplete="address-line2"
                            />
                            <InputError class="mt-2" :message="orderForm.errors.complement" />
                        </div>
                        <div class="col-md-4">
                            <InputLabel for="neighborhood" value="Bairro" />
                            <TextInput
                                id="neighborhood"
                                v-model="orderForm.neighborhood"
                                class="mt-1 w-100"
                                autocomplete="address-level3"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.neighborhood" />
                        </div>
                        <div class="col-md-8">
                            <InputLabel for="city" value="Cidade" />
                            <TextInput
                                id="city"
                                v-model="orderForm.city"
                                class="mt-1 w-100"
                                autocomplete="address-level2"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.city" />
                        </div>
                        <div class="col-md-4">
                            <InputLabel for="state" value="UF" />
                            <TextInput
                                id="state"
                                v-model="orderForm.state"
                                class="mt-1 w-100"
                                maxlength="2"
                                autocomplete="address-level1"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.state" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <SecondaryButton type="button" @click="closeOrderModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :disabled="orderForm.processing">
                        Enviar pedido
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </div>
</template>
