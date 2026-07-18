<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteEnquiryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}
