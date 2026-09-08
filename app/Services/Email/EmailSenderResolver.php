<?php

namespace App\Services\Email;

class EmailSenderResolver
{
    public function resolve(?string $enquiryType = null, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        $type = match ($enquiryType) {
            'gis_fair' => 'gis',
            'gms_fair' => 'gms',
            'jeweal_fair' => 'general',
            'general', 'gis', 'gms' => $enquiryType,
            default => 'general',
        };

        return config('email_management.sender_addresses.'.$type)
            ?: config('mail.from.address');
    }
}
