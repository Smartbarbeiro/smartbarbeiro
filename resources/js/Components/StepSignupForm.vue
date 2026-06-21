<script setup>
import InputError from '@/Components/InputError.vue';
import {
    formatTaxDocumentField,
    isTaxDocumentFieldComplete,
    taxDocumentFieldMaxLength,
    validateTaxDocumentField,
} from '@/utils/taxDocument';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    steps: {
        type: Array,
        required: true,
    },
    processing: {
        type: Boolean,
        default: false,
    },
    plain: {
        type: Boolean,
        default: false,
    },
    hideHeader: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit']);

const activeIndex = ref(0);
const localError = ref('');
const inputRefs = ref([]);
const visiblePasswords = ref({});

const isPasswordStep = (step) => step.type === 'password';

const isTaxDocumentStep = (step) =>
    step.key === 'cpf' || step.key === 'cpf_cnpj';

const isPasswordVisible = (key) => Boolean(visiblePasswords.value[key]);

const inputType = (step) => {
    if (isPasswordStep(step)) {
        return isPasswordVisible(step.key) ? 'text' : 'password';
    }

    return step.type;
};

const togglePasswordVisibility = (key) => {
    visiblePasswords.value[key] = !visiblePasswords.value[key];
};

const passwordToggleLabel = (step) =>
    isPasswordVisible(step.key)
        ? 'Ocultar senha'
        : 'Mostrar senha';

const isLastStep = computed(
    () => activeIndex.value === props.steps.length - 1,
);

const stackHeight = computed(() => {
    const sectionHeight = 4.6875;
    const foldedCount = Math.max(0, props.steps.length - 1 - activeIndex.value);

    if (foldedCount === 0) {
        return `${sectionHeight}rem`;
    }

    return `${sectionHeight + foldedCount * 0.625}rem`;
});

const sectionClass = (index) => ({
    'step-signup-section--folded': index > activeIndex.value,
    'step-signup-section--fold-up': index < activeIndex.value,
    'step-signup-section--active': index === activeIndex.value,
});

const fieldValue = (key) => String(props.form[key] ?? '').trim();

const hasValue = (step) => {
    if (step.skippable && !fieldValue(step.key)) {
        return true;
    }

    const value = fieldValue(step.key);

    if (isTaxDocumentStep(step)) {
        return isTaxDocumentFieldComplete(step.key, value);
    }

    return value.length > 0;
};

const onFieldInput = (step, event) => {
    let { value } = event.target;

    if (isTaxDocumentStep(step)) {
        value = formatTaxDocumentField(step.key, value);
        props.form[step.key] = value;
        event.target.value = value;
        return;
    }

    props.form[step.key] = value;
};

const validateStep = (step) => {
    const value = fieldValue(step.key);

    if (step.required && !value) {
        return step.emptyMessage ?? 'Preencha este campo para continuar.';
    }

    if (step.key === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return 'Informe um e-mail válido.';
    }

    if (step.key === 'username' && value && value.length < 3) {
        return 'O nome da barbearia deve ter pelo menos 3 caracteres.';
    }

    if (step.key === 'password_confirmation' && value !== fieldValue('password')) {
        return 'As senhas não coincidem.';
    }

    const taxDocumentError = validateTaxDocumentField(step.key, value);

    if (taxDocumentError) {
        return taxDocumentError;
    }

    return null;
};

const serverError = (step) => props.form.errors?.[step.key] ?? null;

const focusStep = async (index) => {
    await nextTick();
    inputRefs.value[index]?.focus();
};

const advance = (step, index) => {
    if (props.processing) {
        return;
    }

    localError.value = '';

    const validationMessage = validateStep(step);

    if (validationMessage) {
        localError.value = validationMessage;
        return;
    }

    if (index === props.steps.length - 1) {
        emit('submit');
        return;
    }

    activeIndex.value = index + 1;
    focusStep(index + 1);
};

const onEnter = (step, index, event) => {
    event.preventDefault();
    advance(step, index);
};

watch(
    () => props.form.errors,
    (errors) => {
        if (!errors || Object.keys(errors).length === 0) {
            return;
        }

        const firstErrorIndex = props.steps.findIndex((step) => errors[step.key]);

        if (firstErrorIndex >= 0) {
            activeIndex.value = firstErrorIndex;
        }
    },
    { deep: true },
);

onMounted(() => {
    focusStep(0);
});
</script>

<template>
    <div
        class="step-signup-form"
        :class="{ 'step-signup-form--plain': plain }"
    >
        <header v-if="!hideHeader" class="step-signup-form__header">
            <h1 class="step-signup-form__title">Cadastrar</h1>
            <p class="step-signup-form__subtitle">Preencha as informações</p>
        </header>

        <form class="step-signup-form__body" @submit.prevent>
            <div
                class="step-signup-form__stack"
                :style="{ height: stackHeight }"
            >
                <div
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="step-signup-section"
                    :class="sectionClass(index)"
                    :style="{ zIndex: steps.length - index }"
                >
                    <div
                        v-if="isPasswordStep(step)"
                        class="step-signup-section__field"
                    >
                        <input
                            :ref="(element) => (inputRefs[index] = element)"
                            :id="`step-${step.key}`"
                            :type="inputType(step)"
                            :placeholder="step.placeholder"
                            :autocomplete="step.autocomplete"
                            :required="step.required"
                            class="step-signup-section__input step-signup-section__input--password"
                            :value="form[step.key]"
                            @input="onFieldInput(step, $event)"
                            @keydown.enter="onEnter(step, index, $event)"
                        />

                        <button
                            type="button"
                            class="step-signup-section__toggle-password"
                            :aria-label="passwordToggleLabel(step)"
                            :aria-pressed="isPasswordVisible(step.key)"
                            @click="togglePasswordVisibility(step.key)"
                        >
                            <i
                                class="bi"
                                :class="isPasswordVisible(step.key) ? 'bi-eye-slash' : 'bi-eye'"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </div>

                    <input
                        v-else
                        :ref="(element) => (inputRefs[index] = element)"
                        :id="`step-${step.key}`"
                        :type="step.type"
                        :placeholder="step.placeholder"
                        :autocomplete="step.autocomplete"
                        :inputmode="step.inputmode"
                        :required="step.required"
                        :maxlength="taxDocumentFieldMaxLength(step.key) ?? undefined"
                        class="step-signup-section__input"
                        :value="form[step.key]"
                        @input="onFieldInput(step, $event)"
                        @keydown.enter="onEnter(step, index, $event)"
                    />

                    <div class="step-signup-section__action">
                        <span
                            class="step-signup-section__icon"
                            :class="{ 'step-signup-section__icon--next': hasValue(step) }"
                            aria-hidden="true"
                        >
                            <i :class="step.icon"></i>
                        </span>

                        <button
                            type="button"
                            class="step-signup-section__next"
                            :disabled="processing || !hasValue(step)"
                            :aria-label="isLastStep && index === activeIndex ? 'Cadastrar' : 'Próximo campo'"
                            @click="advance(step, index)"
                        >
                            <i
                                :class="
                                    isLastStep && index === activeIndex
                                        ? 'bi bi-check-lg'
                                        : 'bi bi-arrow-up'
                                "
                            ></i>
                        </button>
                    </div>
                </div>
            </div>

            <p
                v-if="localError"
                class="step-signup-form__error mb-0"
                role="alert"
            >
                {{ localError }}
            </p>

            <InputError
                class="step-signup-form__server-error"
                :message="serverError(steps[activeIndex])"
            />
        </form>
    </div>
</template>
