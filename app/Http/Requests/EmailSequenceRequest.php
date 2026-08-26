<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.sequence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'alpha_dash', 'max:100', Rule::unique('email_sequence_templates', 'code')->ignore($this->route('id'))],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,published,paused,archived'],
        ];
    }
}
