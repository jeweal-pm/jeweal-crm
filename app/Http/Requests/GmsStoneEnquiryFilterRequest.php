<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GmsStoneEnquiryFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'q' => 'nullable|string|max:120',
            'account_type' => ['nullable', Rule::in(['personal', 'business'])],
            'is_seen' => ['nullable', Rule::in(['0', '1'])],
            'is_approved' => ['nullable', Rule::in(['0', '1'])],
            'assigned_to' => 'nullable|integer|exists:users,id',
            'trashed' => ['nullable', Rule::in(['with', 'only'])],
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => ['nullable', Rule::in(['-created_at', 'created_at', 'full_name', '-updated_at'])],
        ];
    }
}
