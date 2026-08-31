<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'is_enabled',
        'account_sid',
        'api_key_sid',
        'api_key_secret',
        'whatsapp_from',
        'daily_limit',
        'max_retry_attempts',
        'retry_delays_seconds',
        'timezone',
    ];

    protected $hidden = [
        'account_sid',
        'api_key_sid',
        'api_key_secret',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'account_sid' => 'encrypted',
        'api_key_sid' => 'encrypted',
        'api_key_secret' => 'encrypted',
        'daily_limit' => 'integer',
        'max_retry_attempts' => 'integer',
        'retry_delays_seconds' => 'array',
    ];

    public function isComplete(): bool
    {
        return filled($this->account_sid)
            && filled($this->api_key_sid)
            && filled($this->api_key_secret)
            && filled($this->whatsapp_from);
    }

    public function maskedValue(string $attribute): ?string
    {
        $value = $this->getAttribute($attribute);

        return $value ? '********'.substr($value, -4) : null;
    }
}
