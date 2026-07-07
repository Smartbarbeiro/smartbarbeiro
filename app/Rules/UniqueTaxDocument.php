<?php

namespace App\Rules;

use App\Models\User;
use App\Support\TaxDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTaxDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = TaxDocument::normalize(is_string($value) ? $value : null);

        if ($normalized === null) {
            return;
        }

        if (User::query()->where('tax_document', $normalized)->exists()) {
            $fail(__('validation.unique', ['attribute' => __('validation.attributes.'.$attribute)]));
        }
    }
}
