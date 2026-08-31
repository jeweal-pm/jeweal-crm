<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTwilioConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $delays = $this->input('retry_delays_seconds');
        if (is_string($delays)) {
            $delays = array_values(array_filter(array_map('trim', explode(',', $delays)), 'strlen'));
        }

        $this->merge([
            'is_enabled' => $this->boolean('is_enabled'),
            'retry_delays_seconds' => $delays,
        ]);
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'account_sid' => ['nullable', 'string', 'max:255'],
            'api_key_sid' => ['nullable', 'string', 'max:255'],
            'api_key_secret' => ['nullable', 'string', 'max:255'],
            'whatsapp_from' => ['required', 'string', 'max:32', 'regex:/^(?:whatsapp:)?\+[1-9][0-9]{7,14}$/i'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_retry_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'retry_delays_seconds' => ['required', 'array', 'min:1', 'max:10'],
            'retry_delays_seconds.*' => ['integer', 'min:1', 'max:86400'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
