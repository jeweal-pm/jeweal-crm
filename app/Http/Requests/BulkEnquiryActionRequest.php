<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEnquiryActionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct'],
            'action' => ['required', Rule::in(['delete', 'restore', 'assign', 'status'])],
            'user_id' => ['nullable', 'required_if:action,assign', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'required_if:action,status', Rule::in(EnquiryStatus::values())],
        ];
    }
}
