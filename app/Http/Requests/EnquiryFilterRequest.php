<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnquiryFilterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('spam') === 'all') {
            $this->merge(['spam' => 'inbox']);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['nullable', Rule::in(EnquiryStatus::values())],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
            'trashed' => ['nullable', 'in:with,only'],
            'spam' => ['nullable', 'in:inbox,suspected,confirmed,not_spam'],
            'record_source' => ['nullable', 'in:gis_enquiry,fair_funnel'],
            'sort' => ['nullable', 'in:created_at,-created_at,assigned_at,-assigned_at,status,-status'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
