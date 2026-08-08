<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.template.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'alpha_dash', 'max:100'],
            'email_type' => ['required', 'in:transactional,marketing,internal'],
            'category' => ['required', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'html_content' => ['required', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string', 'max:150'],
            'sender_email' => ['nullable', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:draft,published,archived'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:64'],
        ];
    }
}
