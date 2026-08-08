<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailMessage extends Model
{
    protected $fillable = [
        'message_id', 'idempotency_key', 'email_subscriber_id', 'email_template_id', 'email_campaign_id',
        'email_enrollment_id', 'email_sequence_step_id', 'message_type', 'to_email', 'to_data', 'cc', 'bcc',
        'subject', 'html_content', 'plain_text_content', 'status', 'provider_message_id', 'attempts',
        'failure_reason', 'queued_at', 'sent_at', 'last_event_at',
    ];

    protected $casts = [
        'to_data' => 'array', 'cc' => 'array', 'bcc' => 'array', 'queued_at' => 'datetime',
        'sent_at' => 'datetime', 'last_event_at' => 'datetime',
    ];

    public function subscriber()
    {
        return $this->belongsTo(EmailSubscriber::class, 'email_subscriber_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function events()
    {
        return $this->hasMany(EmailEvent::class);
    }
}
