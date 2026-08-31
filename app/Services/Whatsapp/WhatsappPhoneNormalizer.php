<?php

namespace App\Services\Whatsapp;

use Illuminate\Validation\ValidationException;

class WhatsappPhoneNormalizer
{
    public function normalize(string $phoneNumber): string
    {
        $value = preg_replace('/^whatsapp:/i', '', trim($phoneNumber));
        $value = preg_replace('/[\s().-]+/', '', (string) $value);

        if (! preg_match('/^\+[1-9][0-9]{7,14}$/', $value)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Use an international phone number in E.164 format, for example +66912345678.',
            ]);
        }

        return $value;
    }
}
