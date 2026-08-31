<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const WAIT_DAILY_LIMIT = 'daily_limit';

    public const WAIT_PROVIDER_FAILURE = 'provider_failure';

    public const WAIT_CONFIGURATION = 'configuration';

    public const WAIT_MANUAL_RETRY = 'manual_retry';

    protected $fillable = [
        'public_id',
        'recipient',
        'recipient_normalized',
        'body',
        'source_module',
        'source_reference',
        'status',
        'wait_reason',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'provider_message_sid',
        'provider_status',
        'provider_error_code',
        'provider_error_message',
        'source_ip',
        'source_ip_hash',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_WAITING)
            ->where(function (Builder $query) {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            });
    }
}
