<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailEnrollment extends Model
{
    protected $fillable = [
        'email_subscriber_id', 'email_sequence_template_id', 'email_campaign_id', 'sequence_version',
        'current_step', 'status', 'enrolled_at', 'last_email_sent_at', 'next_scheduled_at',
        'completed_at', 'exit_reason', 'ab_variant',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime', 'last_email_sent_at' => 'datetime', 'next_scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function subscriber()
    {
        return $this->belongsTo(EmailSubscriber::class, 'email_subscriber_id');
    }

    public function sequence()
    {
        return $this->belongsTo(EmailSequenceTemplate::class, 'email_sequence_template_id');
    }
}
