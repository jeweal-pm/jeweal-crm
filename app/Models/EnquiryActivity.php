<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EnquiryActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_role',
        'action',
        'old_status',
        'new_status',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function enquirable(): MorphTo
    {
        return $this->morphTo();
    }
}
