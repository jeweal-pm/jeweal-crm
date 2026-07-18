<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'target_user_id',
        'action',
        'old_value',
        'new_value',
        'created_at',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];
}
