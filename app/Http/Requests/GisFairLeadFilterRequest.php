<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GisFairLeadFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('enquiry.view.all')
            || $this->user()?->hasCrmPermission('enquiry.view.assigned');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'campaign_id' => ['nullable', 'integer', 'exists:gis_fair_campaigns,id'],
            'status' => ['nullable', Rule::in(['lead_mql', 'sql', 'prospect', 'customer'])],
            'source' => ['nullable', 'string', 'max:40'],
            'marketing_consent' => ['nullable', Rule::in(['yes', 'no'])],
            'trashed' => ['nullable', Rule::in(['with', 'only'])],
        ];
    }
}
