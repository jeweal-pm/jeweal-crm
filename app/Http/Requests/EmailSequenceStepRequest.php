<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailSequenceStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.sequence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'step_number' => ['required', 'integer', 'min:1'], 'email_template_id' => ['required', 'exists:email_templates,id'],
            'delay_value' => ['required', 'integer', 'min:0'], 'delay_unit' => ['required', 'in:minutes,hours,days'],
            'timezone' => ['nullable', 'timezone'], 'business_days_only' => ['sometimes', 'boolean'],
        ];
    }
}
