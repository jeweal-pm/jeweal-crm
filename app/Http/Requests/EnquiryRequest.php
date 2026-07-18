<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'business_type' => 'required|array',
            'business_type.*' => 'string|max:50',
            'email' => 'required|email:rfc|max:100',
            'country' => 'required|string|max:50',
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s().]+$/'],
            'company' => 'required|string|max:100',
            'company_website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'interest_in' => 'required|array',
            'interest_in.*' => 'string|max:80',
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
}
