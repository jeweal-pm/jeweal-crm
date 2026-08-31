<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailTestSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.template.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'enquiry_type' => ['nullable', 'in:general,gis,gms,gis_fair'],
        ];
    }
}
