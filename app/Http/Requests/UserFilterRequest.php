<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('user.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'status' => ['nullable', 'in:active,inactive'],
            'sort' => ['nullable', 'in:name,-name,email,-email,created_at,-created_at'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
