<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GisFairCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('funnel.config.manage') ?? false;
    }

    public function rules(): array
    {
        $campaignId = $this->route('campaign')?->id ?? $this->route('campaign');

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'alpha_dash', 'max:64', Rule::unique('gis_fair_campaigns', 'code')->ignore($campaignId)],
            'edition' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'landing_url' => ['required', 'url:http,https', 'max:2000'],
            'hall' => ['nullable', 'string', 'max:80'],
            'booth' => ['nullable', 'string', 'max:80'],
            'dates_display' => ['nullable', 'string', 'max:150'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'offer_deadline' => ['nullable', 'date'],
            'timezone' => ['required', 'timezone'],
            'code_prefix' => ['required', 'alpha_num', 'max:12'],
            'privacy_notice_version' => ['required', 'string', 'max:40'],
            'privacy_notice_url' => ['nullable', 'url:http,https', 'max:2000'],
            'contact_email' => ['nullable', 'email:rfc', 'max:100'],
            'accepting_submissions' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code'))),
            'code_prefix' => strtoupper(trim((string) $this->input('code_prefix'))),
            'accepting_submissions' => $this->boolean('accepting_submissions'),
        ]);
    }
}
