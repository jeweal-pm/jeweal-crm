<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GisFairLeadSubmission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'campaign_id', 'tracking_link_id', 'tracking_visit_token', 'source',
        'privacy_agreed', 'privacy_notice_version', 'marketing_consent', 'consent_ip',
        'consent_user_agent', 'submitted_at',
    ];

    protected $casts = [
        'privacy_agreed' => 'boolean',
        'marketing_consent' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(GisFairLead::class, 'lead_id');
    }
}
