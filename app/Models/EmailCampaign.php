<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name', 'campaign_type', 'email_segment_id', 'excluded_segment_id', 'email_template_id',
        'email_sequence_template_id', 'scheduled_at', 'timezone', 'sending_limit', 'sender_name',
        'sender_email', 'reply_to_email', 'approval_status', 'status', 'ab_config', 'owner_id',
        'approved_by', 'approved_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime', 'ab_config' => 'array', 'approved_at' => 'datetime',
        'started_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function segment()
    {
        return $this->belongsTo(EmailSegment::class, 'email_segment_id');
    }

    public function excludedSegment()
    {
        return $this->belongsTo(EmailSegment::class, 'excluded_segment_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function sequence()
    {
        return $this->belongsTo(EmailSequenceTemplate::class, 'email_sequence_template_id');
    }

    public function messages()
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function variants()
    {
        return $this->hasMany(EmailCampaignVariant::class);
    }
}
