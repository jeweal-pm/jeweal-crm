<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'ip_hash'];

    protected $casts = ['changes' => 'array'];
}
