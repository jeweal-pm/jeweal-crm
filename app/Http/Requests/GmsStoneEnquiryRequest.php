<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class GmsStoneEnquiryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255',
            'phone_number' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s().]+$/'],
            'country_code' => 'required|string|max:10',
            'account_type' => ['required', Rule::in(['personal', 'business'])],
            'business_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'mailing_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'office_type' => 'nullable|string|max:100',
            'branch_code' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:5000',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email:rfc|max:255',
            'contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-\s().]+$/'],
            'is_seen' => 'sometimes|boolean',
            'is_approved' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge($this->sanitize($this->all()));
    }

    private function sanitize(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($value));
            }

            if (is_array($value)) {
                $input[$key] = $this->sanitize($value);
            }
        }

        return $input;
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
