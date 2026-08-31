<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpRateLimitConfig extends Model
{
    use HasFactory;

    public const MODULE_WHATSAPP = 'whatsapp';

    public const MODULE_JEWEAL = 'jeweal_enquiry';

    public const MODULE_GIS = 'gis_enquiry';

    public const MODULE_GMS = 'gms_enquiry';

    public const MODULE_GIS_FAIR = 'gis_fair';

    protected $fillable = [
        'module',
        'label',
        'is_enabled',
        'max_attempts',
        'window_seconds',
        'cooldown_seconds',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_attempts' => 'integer',
        'window_seconds' => 'integer',
        'cooldown_seconds' => 'integer',
    ];

    public static function defaults(): array
    {
        return [
            self::MODULE_WHATSAPP => 'WhatsApp form',
            self::MODULE_JEWEAL => 'Jeweal enquiry',
            self::MODULE_GIS => 'GIS enquiry',
            self::MODULE_GMS => 'GMS enquiry',
            self::MODULE_GIS_FAIR => 'GIS fair funnel',
        ];
    }
}
