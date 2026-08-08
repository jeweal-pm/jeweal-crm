<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_message_id', 'event_type', 'url', 'ip_hash', 'user_agent_hash', 'metadata', 'occurred_at',
    ];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];
}
