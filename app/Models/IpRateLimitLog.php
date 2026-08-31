<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpRateLimitLog extends Model
{
    use HasFactory;

    public const DECISION_ALLOWED = 'allowed';

    public const DECISION_BLACKLISTED = 'blacklisted';

    public const DECISION_RATE_LIMITED = 'rate_limited';

    public const DECISION_COOLDOWN = 'cooldown';

    protected $fillable = [
        'request_id',
        'module',
        'ip_address',
        'ip_hash',
        'endpoint',
        'decision',
        'http_status',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'http_status' => 'integer',
    ];
}
