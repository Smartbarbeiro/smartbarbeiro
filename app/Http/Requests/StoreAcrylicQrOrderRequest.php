<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcrylicQrOrderRequest extends FormRequest
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
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'max:9'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipient_name' => 'nome do destinatário',
            'phone' => 'telefone',
            'postal_code' => 'CEP',
            'street' => 'rua',
            'number' => 'número',
            'complement' => 'complemento',
            'neighborhood' => 'bairro',
            'city' => 'cidade',
            'state' => 'UF',
        ];
    }
}
