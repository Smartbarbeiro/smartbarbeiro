<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBarbershopMessageRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'send_email' => ['sometimes', 'boolean'],
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'recipient_ids' => ['required_if:audience,selected', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'assunto',
            'body' => 'mensagem',
            'recipient_ids' => 'destinatários',
        ];
    }
}
