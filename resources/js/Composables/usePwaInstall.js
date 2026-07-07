import { computed, ref } from 'vue';

const DISMISS_KEY = 'smartbarbeiro.pwa.install.dismissed';

const deferredPrompt = ref(null);
const isInstalled = ref(false);
const isIos = ref(false);
const isStandalone = ref(false);
const dismissed = ref(false);
let listenersBound = false;

function detectEnvironment() {
    if (typeof window === 'undefined') {
        return;
    }

    const ua = window.navigator.userAgent || '';
    isIos.value = /iphone|ipad|ipod/i.test(ua);
    isStandalone.value =
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;
    isInstalled.value = isStandalone.value;
    dismissed.value = window.localStorage.getItem(DISMISS_KEY) === '1';
}

function onBeforeInstallPrompt(event) {
    event.preventDefault();
    deferredPrompt.value = event;
}

function onAppInstalled() {
    deferredPrompt.value = null;
    isInstalled.value = true;
    isStandalone.value = true;
}

function bindInstallListeners() {
    if (typeof window === 'undefined' || listenersBound) {
        return;
    }

    listenersBound = true;
    detectEnvironment();
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.addEventListener('appinstalled', onAppInstalled);
}

export function usePwaInstall() {
    bindInstallListeners();

    const canPromptInstall = computed(
        () => !isInstalled.value && !dismissed.value && deferredPrompt.value !== null,
    );

    const canShowIosHint = computed(
        () => isIos.value && !isInstalled.value && !dismissed.value,
    );

    const canShowInstallUi = computed(
        () => canPromptInstall.value || canShowIosHint.value,
    );

    const promptInstall = async () => {
        const promptEvent = deferredPrompt.value;

        if (!promptEvent) {
            return false;
        }

        promptEvent.prompt();
        const choice = await promptEvent.userChoice;
        deferredPrompt.value = null;

        if (choice?.outcome === 'accepted') {
            isInstalled.value = true;
            return true;
        }

        return false;
    };

    const dismissInstallUi = () => {
        dismissed.value = true;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(DISMISS_KEY, '1');
        }
    };

    return {
        canPromptInstall,
        canShowIosHint,
        canShowInstallUi,
        isInstalled,
        isIos,
        isStandalone,
        promptInstall,
        dismissInstallUi,
    };
}

export function registerServiceWorker() {
    bindInstallListeners();

    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Installability still works on some browsers without SW registration success.
        });
    });
}
