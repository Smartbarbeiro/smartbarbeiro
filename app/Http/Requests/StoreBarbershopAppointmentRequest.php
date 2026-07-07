<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\BarbershopClientAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StoreBarbershopAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $barbershop = User::query()
            ->where('username', $this->route('username'))
            ->first();

        if ($barbershop === null || $this->user() === null) {
            return false;
        }

        return app(BarbershopClientAccessService::class)->hasSignedUp($barbershop, $this->user())
            && ! $this->user()->isBarbershop();
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
            'client_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
