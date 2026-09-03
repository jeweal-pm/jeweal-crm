<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GisFairDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCrmPermission('enquiry.view.all') ?? false;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['nullable', 'integer', 'exists:gis_fair_campaigns,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'source' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(EnquiryStatus::values())],
            'marketing_consent' => ['nullable', Rule::in(['yes', 'no'])],
            'business_type' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
        ];
    }
}
