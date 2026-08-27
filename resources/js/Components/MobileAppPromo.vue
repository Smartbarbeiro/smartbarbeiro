<script setup>
import Modal from '@/Components/Modal.vue';
import { getMobilePlatform, isMobilePhone } from '@/utils/mobileDetect';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    mobileApp: {
        type: Object,
        required: true,
    },
    withTopbarOffset: {
        type: Boolean,
        default: false,
    },
});

const STORAGE_KEY = 'tesora.plan-builder.mobile-app-promo.dismissed';

const dismissed = ref(false);
const showModal = ref(false);
const isPhone = ref(false);

const platform = computed(() => getMobilePlatform());

const storeUrl = computed(() => {
    if (platform.value === 'ios') {
        return props.mobileApp.app_store_url || null;
    }

    if (platform.value === 'android') {
        return props.mobileApp.play_store_url || null;
    }

    return props.mobileApp.play_store_url || props.mobileApp.app_store_url || null;
});

const storeLabel = computed(() =>
    platform.value === 'ios' ? 'App Store' : 'Google Play',
);

const appName = computed(() => props.mobileApp.name || 'Tesora');

const isVisible = computed(
    () => isPhone.value && !dismissed.value && Boolean(storeUrl.value),
);

const dismiss = () => {
    dismissed.value = true;
    showModal.value = false;
    localStorage.setItem(STORAGE_KEY, '1');
};

onMounted(() => {
    isPhone.value = isMobilePhone();

    if (localStorage.getItem(STORAGE_KEY) === '1') {
        dismissed.value = true;
        return;
    }

    if (isPhone.value && storeUrl.value) {
        showModal.value = true;
    }
});
</script>

<template>
    <div v-if="isVisible" class="mobile-app-promo">
        <div
            class="mobile-app-promo__bar"
            :class="{ 'mobile-app-promo__bar--with-topbar': withTopbarOffset }"
            role="region"
            aria-label="Baixar aplicativo"
        >
            <p class="mobile-app-promo__bar-text mb-0">
                <i class="bi bi-phone me-2" aria-hidden="true"></i>
                Use o app {{ appName }} para assinar planos com mais facilidade.
            </p>

            <div class="mobile-app-promo__bar-actions">
                <a
                    :href="storeUrl"
                    class="btn btn-sm btn-light mobile-app-promo__cta"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Baixar na {{ storeLabel }}
                </a>

                <button
                    type="button"
                    class="mobile-app-promo__close"
                    aria-label="Continuar no site"
                    @click="dismiss"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <Modal :show="showModal" max-width="sm" @close="dismiss">
            <div class="mobile-app-promo__modal">
                <div class="modal-header border-secondary">
                    <h2 class="modal-title h5 mb-0">
                        Baixe o app {{ appName }}
                    </h2>
                </div>

                <div class="modal-body">
                    <p class="mb-3">
                        Você está acessando pelo celular. Para montar e assinar
                        planos com pagamento nativo e uma experiência otimizada,
                        baixe o aplicativo gratuito.
                    </p>

                    <a
                        :href="storeUrl"
                        class="btn btn-primary w-100"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="bi bi-download me-2" aria-hidden="true"></i>
                        Baixar na {{ storeLabel }}
                    </a>
                </div>

                <div class="modal-footer border-secondary">
                    <button
                        type="button"
                        class="btn btn-outline-light btn-sm"
                        @click="dismiss"
                    >
                        Continuar no site
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
