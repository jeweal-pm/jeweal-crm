<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIpRateLimitConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_enabled' => $this->boolean('is_enabled')]);
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10000'],
            'window_seconds' => ['required', 'integer', 'min:60', 'max:31536000'],
            'cooldown_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
        ];
    }
}
