<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GisEnquiryRequest extends FormRequest
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email:rfc|max:100',
            'phone_number' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s().]+$/'],
            'inquiry' => 'required|string|max:100',
            'message' => 'nullable|string|max:2000',
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
