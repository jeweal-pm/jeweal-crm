<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSuppressionList extends Model
{
    protected $fillable = ['email', 'category', 'reason', 'source', 'metadata', 'suppressed_at'];

    protected $casts = ['metadata' => 'array', 'suppressed_at' => 'datetime'];
}
