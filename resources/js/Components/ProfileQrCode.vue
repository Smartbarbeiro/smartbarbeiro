<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import QRCode from 'qrcode';
import { formatPhone } from '@/utils/formatPhone';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

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
    ownerDashboard: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const dataUrl = ref('');
const error = ref('');
const showOrderModal = ref(false);
const expanded = defineModel('expanded', { type: Boolean, default: false });
const qrPanel = ref(null);
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

const activeAcrylicOrder = computed(() => {
    const order = props.acrylicOrder;

    if (!order || order.status === 'shipped') {
        return null;
    }

    return order;
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

const focusQrPanel = () => {
    const el = qrPanel.value;

    if (!el) {
        return;
    }

    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.focus({ preventScroll: true });
};

const toggleShareQr = async () => {
    expanded.value = !expanded.value;

    if (!expanded.value) {
        return;
    }

    await nextTick();
    focusQrPanel();
};

defineExpose({ focusQrPanel });

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

onMounted(() => {
    if (props.defaultExpanded) {
        expanded.value = true;
    }

    generate();
});

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

watch(
    () => orderForm.phone,
    (value) => {
        const formatted = formatPhone(value);

        if (formatted !== value) {
            orderForm.phone = formatted;
        }
    },
);
</script>

<template>
    <div class="profile-qr-section">
        <button
            v-if="!hideShareButton"
            type="button"
            class="btn-share-qrcode"
            :aria-expanded="expanded"
            @click="toggleShareQr"
        >
            <i class="bi bi-qr-code me-2" aria-hidden="true"></i>
            Compartilhar Qr-code
        </button>

        <div
            v-show="expanded"
            ref="qrPanel"
            tabindex="-1"
            :class="
                ownerDashboard
                    ? 'profile-qr-panel profile-qr-panel--owner-dashboard'
                    : 'app-card p-3 mt-3'
            "
        >
            <template v-if="ownerDashboard">
                <p class="profile-qr-panel__url font-monospace small text-break mb-0">
                    {{ url }}
                </p>

                <div class="profile-qr-panel__qr-frame">
                    <img
                        v-if="dataUrl"
                        :src="dataUrl"
                        :alt="`${label} para ${url}`"
                        class="profile-qr-panel__image"
                        :width="size"
                        :height="size"
                    />
                    <div
                        v-else
                        class="profile-qr-panel__placeholder"
                        :style="{ width: `${size}px`, height: `${size}px` }"
                    >
                        {{ error || 'Gerando…' }}
                    </div>
                </div>

                <p class="profile-qr-panel__hint mb-0">
                    Escaneie para abrir o perfil da sua barbearia no celular.
                </p>

                <div class="profile-qr-panel__actions">
                    <button
                        type="button"
                        class="btn btn-yellow"
                        :disabled="!dataUrl"
                        @click="download"
                    >
                        <i class="bi bi-download me-2" aria-hidden="true"></i>
                        Baixar PNG
                    </button>

                    <button
                        v-if="showAcrylicOrder && !activeAcrylicOrder"
                        type="button"
                        class="btn btn-yellow"
                        @click="openOrderModal"
                    >
                        <i class="bi bi-printer me-2" aria-hidden="true"></i>
                        Pedir qr-code físico
                    </button>

                    <div
                        v-else-if="showAcrylicOrder && activeAcrylicOrder"
                        class="profile-qr-panel__order-status small"
                    >
                        <span
                            class="badge"
                            :class="acrylicStatusClass(activeAcrylicOrder.status)"
                        >
                            {{ activeAcrylicOrder.status_label }}
                        </span>
                        <p class="mb-0 mt-2">
                            Pedido em
                            {{
                                new Date(
                                    activeAcrylicOrder.created_at,
                                ).toLocaleDateString('pt-BR')
                            }}.
                        </p>
                    </div>
                </div>
            </template>

            <template v-else>
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
                    <i class="bi bi-download me-2" aria-hidden="true"></i>
                    Baixar PNG
                </SecondaryButton>

                <SecondaryButton
                    v-if="showAcrylicOrder && !activeAcrylicOrder"
                    type="button"
                    @click="openOrderModal"
                >
                    <i class="bi bi-printer me-2" aria-hidden="true"></i>
                    Pedir qr-code físico
                </SecondaryButton>

                <div
                    v-else-if="showAcrylicOrder && activeAcrylicOrder"
                    class="small"
                    style="max-width: 16rem"
                >
                    <span
                        class="badge"
                        :class="acrylicStatusClass(activeAcrylicOrder.status)"
                    >
                        {{ activeAcrylicOrder.status_label }}
                    </span>
                    <p class="text-secondary mb-0 mt-2">
                        Pedido em {{ new Date(activeAcrylicOrder.created_at).toLocaleDateString('pt-BR') }}.
                    </p>
                </div>

                <p class="small text-secondary mb-0" style="max-width: 16rem">
                    Escaneie para abrir este perfil no celular.
                </p>
            </div>
            </div>
            </template>
        </div>

        <Modal
            :show="showOrderModal"
            max-width="lg"
            variant="light"
            @close="closeOrderModal"
        >
            <form class="acrylic-qr-order-modal" @submit.prevent="submitOrder">
                <div class="modal-header border-secondary-subtle">
                    <h2 class="modal-title h5 mb-0">Pedir QR code físico</h2>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Fechar"
                        @click="closeOrderModal"
                    />
                </div>
                <div class="modal-body">
                    <div class="acrylic-qr-order-modal__hero">
                        <img
                            src="/images/acrylic-qr-stand.png"
                            alt="Suporte de acrílico com QR code"
                            class="acrylic-qr-order-modal__image"
                            width="320"
                            height="320"
                        />
                        <p class="acrylic-qr-order-modal__lead mb-0">
                            Enviaremos grátis seu QR code em suporte de acrílico.
                        </p>
                    </div>

                    <div class="row g-3 acrylic-qr-order-modal__fields">
                        <div class="col-md-6">
                            <TextInput
                                id="recipient_name"
                                v-model="orderForm.recipient_name"
                                class="acrylic-qr-order-modal__input w-100"
                                placeholder="Nome do destinatário"
                                aria-label="Nome do destinatário"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.recipient_name" />
                        </div>
                        <div class="col-md-6">
                            <TextInput
                                id="phone"
                                v-model="orderForm.phone"
                                class="acrylic-qr-order-modal__input w-100"
                                inputmode="tel"
                                autocomplete="tel"
                                maxlength="15"
                                placeholder="(67) 99999-9999"
                                aria-label="Telefone"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.phone" />
                        </div>
                        <div class="col-md-4">
                            <TextInput
                                id="postal_code"
                                v-model="orderForm.postal_code"
                                class="acrylic-qr-order-modal__input w-100"
                                inputmode="numeric"
                                autocomplete="postal-code"
                                maxlength="9"
                                placeholder="CEP"
                                aria-label="CEP"
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
                            <TextInput
                                id="street"
                                v-model="orderForm.street"
                                class="acrylic-qr-order-modal__input w-100"
                                autocomplete="address-line1"
                                placeholder="Rua"
                                aria-label="Rua"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.street" />
                        </div>
                        <div class="col-md-3">
                            <TextInput
                                id="number"
                                ref="numberInput"
                                v-model="orderForm.number"
                                class="acrylic-qr-order-modal__input w-100"
                                autocomplete="off"
                                placeholder="Número"
                                aria-label="Número"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.number" />
                        </div>
                        <div class="col-md-5">
                            <TextInput
                                id="complement"
                                v-model="orderForm.complement"
                                class="acrylic-qr-order-modal__input w-100"
                                autocomplete="address-line2"
                                placeholder="Complemento (opcional)"
                                aria-label="Complemento"
                            />
                            <InputError class="mt-2" :message="orderForm.errors.complement" />
                        </div>
                        <div class="col-md-4">
                            <TextInput
                                id="neighborhood"
                                v-model="orderForm.neighborhood"
                                class="acrylic-qr-order-modal__input w-100"
                                autocomplete="address-level3"
                                placeholder="Bairro"
                                aria-label="Bairro"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.neighborhood" />
                        </div>
                        <div class="col-md-8">
                            <TextInput
                                id="city"
                                v-model="orderForm.city"
                                class="acrylic-qr-order-modal__input w-100"
                                autocomplete="address-level2"
                                placeholder="Cidade"
                                aria-label="Cidade"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.city" />
                        </div>
                        <div class="col-md-4">
                            <TextInput
                                id="state"
                                v-model="orderForm.state"
                                class="acrylic-qr-order-modal__input w-100"
                                maxlength="2"
                                autocomplete="address-level1"
                                placeholder="UF"
                                aria-label="UF"
                                required
                            />
                            <InputError class="mt-2" :message="orderForm.errors.state" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary-subtle">
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
