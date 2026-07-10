<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatPhone } from '@/utils/formatPhone';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    date: {
        type: String,
        required: true,
    },
    time: {
        type: String,
        default: null,
    },
    owner: {
        type: Object,
        required: true,
    },
    employees: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    scheduled_at: '',
    service_key: '',
    service_label: '',
    package_type: null,
    assignee: 'owner',
    client_user_id: '',
    guest_name: '',
    guest_phone: '',
    client_notes: '',
});

const selectedService = computed(() =>
    props.services.find((service) => service.key === form.service_key) ?? null,
);

watch(
    () => [props.show, props.date, props.time],
    ([show, date, time]) => {
        if (!show || !date || !time) {
            return;
        }

        form.clearErrors();
        form.scheduled_at = `${date}T${time}:00`;
        form.service_key = props.services[0]?.key ?? '';
        form.assignee = 'owner';
        form.client_user_id = '';
        form.guest_name = '';
        form.guest_phone = '';
        form.client_notes = '';
    },
    { immediate: true },
);

watch(
    () => form.service_key,
    () => {
        if (!selectedService.value) {
            return;
        }

        form.service_label = selectedService.value.label;
        form.package_type = selectedService.value.package_type;
    },
    { immediate: true },
);

watch(
    () => form.guest_phone,
    (value) => {
        const formatted = formatPhone(value);

        if (formatted !== value) {
            form.guest_phone = formatted;
        }
    },
);

const submit = () => {
    form
        .transform((data) => ({
            scheduled_at: data.scheduled_at,
            service_label: data.service_label,
            package_type: data.package_type,
            barbershop_employee_id:
                data.assignee === 'owner' ? null : Number(data.assignee),
            client_user_id: data.client_user_id
                ? Number(data.client_user_id)
                : null,
            guest_name: data.client_user_id ? null : data.guest_name,
            guest_phone: data.guest_phone || null,
            client_notes: data.client_notes || null,
        }))
        .post(route('agenda.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
};
</script>

<template>
    <div
        v-if="show"
        class="book-appointment-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="schedule-owner-title"
    >
        <div class="book-appointment-modal__backdrop" @click="emit('close')" />

        <div class="book-appointment-modal__panel">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 id="schedule-owner-title" class="h5 mb-1">
                        Agendar horário
                    </h2>
                    <p class="book-appointment-modal__subtitle mb-0">
                        {{ time }} · {{ date }}
                    </p>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    aria-label="Fechar"
                    @click="emit('close')"
                />
            </div>

            <form class="book-appointment-modal__form d-flex flex-column gap-3" @submit.prevent="submit">
                <div>
                    <InputLabel for="schedule-service" value="Serviço" />
                    <select
                        id="schedule-service"
                        v-model="form.service_key"
                        class="form-select book-appointment-modal__select mt-1"
                        required
                    >
                        <option
                            v-for="service in services"
                            :key="service.key"
                            :value="service.key"
                        >
                            {{ service.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.service_label" />
                </div>

                <div>
                    <InputLabel for="schedule-assignee" value="Quem atende" />
                    <select
                        id="schedule-assignee"
                        v-model="form.assignee"
                        class="form-select book-appointment-modal__select mt-1"
                        required
                    >
                        <option value="owner">{{ owner.name }} (proprietário)</option>
                        <option
                            v-for="employee in employees.filter((item) => item.is_active)"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >
                            {{ employee.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <InputLabel for="schedule-client" value="Cliente cadastrado (opcional)" />
                    <select
                        id="schedule-client"
                        v-model="form.client_user_id"
                        class="form-select book-appointment-modal__select mt-1"
                    >
                        <option value="">Cliente avulso</option>
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="String(client.id)"
                        >
                            {{ client.name }}
                        </option>
                    </select>
                </div>

                <div v-if="!form.client_user_id">
                    <InputLabel for="schedule-guest-name" value="Nome do cliente" />
                    <TextInput
                        id="schedule-guest-name"
                        v-model="form.guest_name"
                        type="text"
                        class="book-appointment-modal__input mt-1 w-100"
                        placeholder="Ex.: João Silva"
                        required
                    />
                    <InputError class="mt-1" :message="form.errors.guest_name" />
                </div>

                <div>
                    <InputLabel for="schedule-guest-phone" value="Telefone (opcional)" />
                    <TextInput
                        id="schedule-guest-phone"
                        v-model="form.guest_phone"
                        type="text"
                        class="book-appointment-modal__input mt-1 w-100"
                        inputmode="tel"
                        autocomplete="tel"
                        maxlength="15"
                        placeholder="(67) 99999-9999"
                    />
                </div>

                <div>
                    <InputLabel for="schedule-notes" value="Observações (opcional)" />
                    <textarea
                        id="schedule-notes"
                        v-model="form.client_notes"
                        class="form-control book-appointment-modal__input mt-1"
                        rows="2"
                        maxlength="500"
                    />
                </div>

                <InputError :message="form.errors.scheduled_at" />

                <div class="d-flex justify-content-end gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="emit('close')"
                    >
                        Cancelar
                    </button>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        Confirmar agendamento
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>
