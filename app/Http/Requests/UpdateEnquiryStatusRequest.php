<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnquiryStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['required', Rule::in(EnquiryStatus::values())],
        ];
    }
}
