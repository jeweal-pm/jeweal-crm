<?php

namespace App\Services\Email;

class EmailSenderResolver
{
    public function resolve(?string $enquiryType = null, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        $type = $this->senderType($enquiryType);

        return config('email_management.sender_addresses.'.$type)
            ?: config('mail.from.address');
    }

    public function resolveName(?string $enquiryType = null, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        return config('email_management.sender_names.'.$this->senderType($enquiryType))
            ?: config('mail.from.name');
    }

    private function senderType(?string $enquiryType): string
    {
        return match ($enquiryType) {
            'gis_fair' => 'gis',
            'gms_fair' => 'gms',
            'jeweal_fair' => 'general',
            'general', 'gis', 'gms' => $enquiryType,
            default => 'general',
        };
    }
}
