<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBarbershopEmployeeRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('barbershop_employees', 'name')
                    ->where('barbershop_user_id', $this->user()->id),
            ],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
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
