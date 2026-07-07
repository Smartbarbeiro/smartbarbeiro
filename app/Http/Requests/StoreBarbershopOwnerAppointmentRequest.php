<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBarbershopOwnerAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBarbershop() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'service_label' => ['required', 'string', 'max:120'],
            'package_type' => ['nullable', 'string', 'max:40'],
            'barbershop_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('barbershop_employees', 'id')
                    ->where('barbershop_user_id', $this->user()->id),
            ],
            'client_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_name' => ['required_without:client_user_id', 'nullable', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'client_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guest_name.required_without' => 'Informe o nome do cliente ou selecione um cadastrado.',
            'service_label.required' => 'Escolha o serviço.',
        ];
    }
}
