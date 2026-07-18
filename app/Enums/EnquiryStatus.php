<?php

namespace App\Enums;

class EnquiryStatus
{
    public const LEAD_MQL = 'lead_mql';
    public const SQL = 'sql';
    public const PROSPECT = 'prospect';
    public const CUSTOMER = 'customer';

    public static function values(): array
    {
        return [
            self::LEAD_MQL,
            self::SQL,
            self::PROSPECT,
            self::CUSTOMER,
        ];
    }

    public static function label(string $status): string
    {
        return [
            self::LEAD_MQL => 'Lead / MQL',
            self::SQL => 'SQL',
            self::PROSPECT => 'Prospect',
            self::CUSTOMER => 'Customer',
        ][$status] ?? $status;
    }
}
