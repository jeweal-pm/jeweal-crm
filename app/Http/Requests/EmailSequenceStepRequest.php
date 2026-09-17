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
            'step_number' => ['required', 'integer', 'min:1'],
            'content_mode' => ['required', 'in:template,custom'],
            'email_template_id' => ['nullable', 'required_if:content_mode,template', 'exists:email_templates,id'],
            'subject' => ['nullable', 'required_if:content_mode,custom', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'html_content' => ['nullable', 'required_if:content_mode,custom', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:64'],
            'delay_value' => ['required', 'integer', 'min:0'], 'delay_unit' => ['required', 'in:minutes,hours,days'],
            'timezone' => ['nullable', 'timezone'], 'business_days_only' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('content_mode')) {
            $this->merge(['content_mode' => $this->filled('email_template_id') ? 'template' : 'custom']);
        }
    }
}
