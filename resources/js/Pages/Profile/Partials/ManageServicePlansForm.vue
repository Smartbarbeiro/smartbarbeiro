<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    servicePlans: {
        type: Object,
        required: true,
    },
});

const packageDefaults = (type) =>
    props.servicePlans.packages.find((pkg) => pkg.type === type) ?? {
        type,
        monthly_price: 0,
        is_enabled: true,
    };

const form = useForm({
    packages: {
        cut: {
            monthly_price: packageDefaults('cut').monthly_price,
            is_enabled: packageDefaults('cut').is_enabled,
        },
        cut_beard: {
            monthly_price: packageDefaults('cut_beard').monthly_price,
            is_enabled: packageDefaults('cut_beard').is_enabled,
        },
    },
    addons: (props.servicePlans.addons ?? []).map((addon) => ({
        id: addon.id,
        name: addon.name,
        monthly_price: addon.monthly_price,
        is_enabled: addon.is_enabled,
        sort_order: addon.sort_order,
    })),
    deleted_addon_ids: [],
});

const addAddon = () => {
    form.addons.push({
        id: null,
        name: '',
        monthly_price: 0,
        is_enabled: true,
        sort_order: form.addons.length,
    });
};

const removeAddon = (index) => {
    const addon = form.addons[index];

    if (addon.id) {
        form.deleted_addon_ids.push(addon.id);
    }

    form.addons.splice(index, 1);
};

const submit = () => {
    const payload = {
        packages: form.packages,
        addons: form.addons
            .filter((addon) => addon.name.trim() !== '')
            .map((addon, index) => ({
                ...addon,
                sort_order: index,
            })),
        deleted_addon_ids: form.deleted_addon_ids,
    };

    form
        .transform(() => payload)
        .put(route('profile.service-plans.update'), {
            preserveScroll: true,
        });
};

const hasConfiguredPackage = computed(() =>
    form.packages.cut.monthly_price > 0 || form.packages.cut_beard.monthly_price > 0,
);
</script>

<template>
    <section>
        <header>
            <h2 id="planos-de-servico" class="h5 fw-semibold mb-1">
                Planos de serviço
            </h2>
            <p class="text-secondary small mb-0">
                Defina os preços dos pacotes padrão (Corte e Corte + Barba) e
                adicione opcionais personalizados com valores próprios.
            </p>
        </header>

        <div
            v-if="!hasConfiguredPackage"
            class="alert alert-warning mt-3 mb-0"
            role="alert"
        >
            Configure ao menos um pacote padrão com preço maior que zero para
            exibir o montador de planos no seu perfil público.
        </div>

        <form class="mt-4" @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h3 class="h6 fw-semibold mb-3">Corte Cabelo</h3>
                        <p class="small text-secondary mb-3">
                            Pacote padrão disponível para todas as barbearias.
                        </p>

                        <div class="form-check mb-3">
                            <input
                                id="cut_enabled"
                                v-model="form.packages.cut.is_enabled"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <InputLabel
                                for="cut_enabled"
                                value="Oferecer este pacote"
                                class="form-check-label"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="cut_price"
                                value="Preço mensal (BRL)"
                            />
                            <TextInput
                                id="cut_price"
                                v-model="form.packages.cut.monthly_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 w-100"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors['packages.cut.monthly_price']"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h3 class="h6 fw-semibold mb-3">Corte Cabelo + Barba</h3>
                        <p class="small text-secondary mb-3">
                            Pacote padrão disponível para todas as barbearias.
                        </p>

                        <div class="form-check mb-3">
                            <input
                                id="cut_beard_enabled"
                                v-model="form.packages.cut_beard.is_enabled"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <InputLabel
                                for="cut_beard_enabled"
                                value="Oferecer este pacote"
                                class="form-check-label"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="cut_beard_price"
                                value="Preço mensal (BRL)"
                            />
                            <TextInput
                                id="cut_beard_price"
                                v-model="form.packages.cut_beard.monthly_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 w-100"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors['packages.cut_beard.monthly_price']"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div
                    class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"
                >
                    <div>
                        <h3 class="h6 fw-semibold mb-1">Opcionais personalizados</h3>
                        <p class="small text-secondary mb-0">
                            Ex.: sobrancelha, hidratação, pigmentação.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        @click="addAddon"
                    >
                        Adicionar opcional
                    </button>
                </div>

                <p
                    v-if="form.addons.length === 0"
                    class="small text-secondary mb-0"
                >
                    Nenhum opcional cadastrado.
                </p>

                <div
                    v-for="(addon, index) in form.addons"
                    :key="addon.id ?? `new-${index}`"
                    class="border rounded-3 p-3 mb-3"
                >
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <InputLabel
                                :for="`addon_name_${index}`"
                                value="Nome do opcional"
                            />
                            <TextInput
                                :id="`addon_name_${index}`"
                                v-model="addon.name"
                                type="text"
                                class="mt-1 w-100"
                                placeholder="Ex.: Sobrancelha"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors[`addons.${index}.name`]"
                            />
                        </div>

                        <div class="col-md-3">
                            <InputLabel
                                :for="`addon_price_${index}`"
                                value="Preço mensal (BRL)"
                            />
                            <TextInput
                                :id="`addon_price_${index}`"
                                v-model="addon.monthly_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 w-100"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors[`addons.${index}.monthly_price`]"
                            />
                        </div>

                        <div class="col-md-2">
                            <div class="form-check mb-2">
                                <input
                                    :id="`addon_enabled_${index}`"
                                    v-model="addon.is_enabled"
                                    type="checkbox"
                                    class="form-check-input"
                                />
                                <InputLabel
                                    :for="`addon_enabled_${index}`"
                                    value="Ativo"
                                    class="form-check-label"
                                />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm w-100"
                                @click="removeAddon(index)"
                            >
                                Remover
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <PrimaryButton :disabled="form.processing">
                    Salvar planos
                </PrimaryButton>

                <p
                    v-if="$page.props.flash?.status === 'service-plans-updated'"
                    class="text-secondary small mb-0"
                >
                    Planos salvos.
                </p>
            </div>
        </form>
    </section>
</template>
