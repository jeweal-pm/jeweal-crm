<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailAutomationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.config.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_enabled' => ['sometimes', 'boolean'], 'customer_template_id' => ['nullable', 'exists:email_templates,id'],
            'customer_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:604800'],
            'internal_enabled' => ['sometimes', 'boolean'], 'internal_template_id' => ['nullable', 'exists:email_templates,id'],
            'internal_to' => ['nullable', 'array'], 'internal_to.*' => ['email'],
            'internal_cc' => ['nullable', 'array'], 'internal_cc.*' => ['email'],
            'internal_bcc' => ['nullable', 'array'], 'internal_bcc.*' => ['email'],
            'internal_assignment_mode' => ['required', 'in:config,assigned,config_and_assigned'],
            'reminder_after_minutes' => ['nullable', 'integer', 'min:1'],
            'welcome_enabled' => ['sometimes', 'boolean'], 'welcome_template_id' => ['nullable', 'exists:email_templates,id'],
            'welcome_delay_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation()
    {
        $data = $this->all();
        foreach (['internal_to', 'internal_cc', 'internal_bcc'] as $field) {
            $value = $data[$field] ?? [];
            $values = is_array($value) ? $value : [$value];
            $emails = [];

            foreach ($values as $emailList) {
                if (is_string($emailList)) {
                    array_push($emails, ...preg_split('/[\s,;]+/', trim($emailList), -1, PREG_SPLIT_NO_EMPTY));
                }
            }

            $data[$field] = $emails;
        }
        foreach (['customer_enabled', 'internal_enabled', 'welcome_enabled'] as $field) {
            $data[$field] = $this->boolean($field);
        }
        $this->merge($data);
    }
}
