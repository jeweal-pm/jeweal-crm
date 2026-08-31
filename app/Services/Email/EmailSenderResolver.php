<?php

namespace App\Services\Email;

class EmailSenderResolver
{
    public function resolve(?string $enquiryType = null, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        $type = in_array($enquiryType, ['general', 'gis', 'gms'], true) ? $enquiryType : 'general';
        if ($enquiryType === 'gis_fair') {
            $type = 'gis';
        }

        return config('email_management.sender_addresses.'.$type)
            ?: config('mail.from.address');
    }
}
