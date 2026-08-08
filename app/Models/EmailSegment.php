<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSegment extends Model
{
    protected $fillable = ['name', 'code', 'segment_type', 'conditions', 'status', 'created_by', 'updated_by'];

    protected $casts = ['conditions' => 'array'];

    public function subscribers()
    {
        return $this->belongsToMany(EmailSubscriber::class, 'email_segment_memberships')
            ->withPivot('is_snapshot', 'added_at');
    }
}
