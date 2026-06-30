<script setup>
import { formatBarbershopUsernameInput } from '@/utils/formatBarbershopUsername';
import { useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel({
    type: String,
    default: '',
});

const attrs = useAttrs();

const onInput = (event) => {
    const value = formatBarbershopUsernameInput(event.target.value);

    model.value = value;
    event.target.value = value;
};
</script>

<template>
    <div class="profile-barbershop-name-field">
        <input
            :value="model"
            type="text"
            class="profile-barbershop-name-field__input"
            placeholder="NOME DA BARBEARIA"
            v-bind="attrs"
            @input="onInput"
        />

        <div class="profile-barbershop-name-field__icon" aria-hidden="true">
            <i class="bi bi-shop"></i>
        </div>
    </div>
</template>
