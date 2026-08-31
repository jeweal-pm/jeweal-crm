<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GisFairTrackingLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('funnel.config.manage') ?? false;
    }

    public function rules(): array
    {
        $linkId = $this->route('link')?->id ?? $this->route('link');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'alpha_dash', 'max:64', Rule::unique('gis_fair_tracking_links', 'code')->ignore($linkId)],
            'destination_url' => ['nullable', 'url:http,https', 'max:2000'],
            'source' => ['nullable', 'string', 'max:80'],
            'medium' => ['nullable', 'string', 'max:80'],
            'content' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
