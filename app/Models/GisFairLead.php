<?php

namespace App\Models;

use App\Contracts\Enquirable as EnquirableContract;
use App\Models\Concerns\HasEnquiryWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GisFairLead extends Model implements EnquirableContract
{
    use HasEnquiryWorkflow;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'campaign_id', 'tracking_link_id', 'tracking_visit_token', 'fair_code',
        'first_name', 'last_name', 'email', 'company', 'business_type', 'stores',
        'country', 'phone_iso', 'phone_local', 'phone_e164', 'phone_dial_code',
        'current_system', 'interests', 'source', 'marketing_consent',
        'marketing_consent_at', 'marketing_consent_withdrawn_at', 'privacy_agreed',
        'privacy_agreed_at', 'privacy_notice_version', 'consent_ip', 'consent_user_agent',
        'submission_count', 'last_submitted_at', 'confirmation_sent_at',
        'confirmation_send_count',
    ];

    protected $attributes = [
        'status' => 'lead_mql',
        'spam_status' => 'clean',
        'spam_score' => 0,
    ];

    protected $casts = [
        'stores' => 'integer',
        'interests' => 'array',
        'marketing_consent' => 'boolean',
        'marketing_consent_at' => 'datetime',
        'marketing_consent_withdrawn_at' => 'datetime',
        'privacy_agreed' => 'boolean',
        'privacy_agreed_at' => 'datetime',
        'last_submitted_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
        'counts_for_sale_kpi' => 'boolean',
        'spam_reasons' => 'array',
        'spam_checked_at' => 'datetime',
        'spam_reviewed_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(GisFairCampaign::class, 'campaign_id');
    }

    public function trackingLink()
    {
        return $this->belongsTo(GisFairTrackingLink::class, 'tracking_link_id');
    }

    public function submissions()
    {
        return $this->hasMany(GisFairLeadSubmission::class, 'lead_id');
    }
}
