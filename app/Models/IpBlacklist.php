<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'ip_hash',
        'reason',
        'is_active',
        'blocked_until',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'blocked_until' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
