<?php

namespace App\Http\Requests;

use App\Models\BarbershopAppointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarbershopAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BarbershopAppointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('update', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['confirm', 'reject', 'cancel', 'complete', 'assign']),
            ],
            'barbershop_employee_id' => ['nullable', 'integer', 'exists:barbershop_employees,id'],
        ];
    }
}
