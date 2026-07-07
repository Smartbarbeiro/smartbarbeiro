<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminBroadcastMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
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
            'audience' => ['required', Rule::in(['barbershops', 'clients', 'all', 'selected'])],
            'recipient_ids' => ['required_if:audience,selected', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
