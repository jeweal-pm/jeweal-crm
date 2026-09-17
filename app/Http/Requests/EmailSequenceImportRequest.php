<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailSequenceImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('email.sequence.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'json_file' => ['nullable', 'file', 'mimes:json,txt', 'max:5120'],
            'payload' => ['nullable', 'string', 'max:2000000'],
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('json_file') && trim((string) $this->input('payload')) === '') {
                $validator->errors()->add('payload', 'Upload a JSON file or paste a JSON payload.');
            }
        });
    }
}
