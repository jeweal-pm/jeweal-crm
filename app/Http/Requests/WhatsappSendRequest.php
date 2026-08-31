<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:32', 'regex:/^(?:whatsapp:)?\+[0-9\s().-]{8,24}$/i'],
            'message' => ['required', 'string', 'max:1600'],
            'reference_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Use an international phone number, for example +66912345678.',
        ];
    }
}
