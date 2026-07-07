<?php

namespace App\Http\Requests;

use App\Models\BarbershopEmployee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarbershopEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BarbershopEmployee $employee */
        $employee = $this->route('employee');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('barbershop_employees', 'name')
                    ->where('barbershop_user_id', $this->user()->id)
                    ->ignore($employee->id),
            ],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do funcionário.',
            'commission_percent.required' => 'Informe a comissão do funcionário.',
            'commission_percent.max' => 'A comissão não pode ser maior que 100%.',
        ];
    }
}
