<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GisFairCampaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'edition', 'status', 'landing_url', 'hall', 'booth', 'dates_display',
        'starts_at', 'ends_at', 'offer_deadline', 'timezone', 'code_prefix',
        'privacy_notice_version', 'privacy_notice_url', 'contact_email',
        'accepting_submissions', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'offer_deadline' => 'datetime',
        'accepting_submissions' => 'boolean',
    ];

    public function leads()
    {
        return $this->hasMany(GisFairLead::class, 'campaign_id');
    }

    public function trackingLinks()
    {
        return $this->hasMany(GisFairTrackingLink::class, 'campaign_id');
    }

    public function isOpenForSubmissions(): bool
    {
        if ($this->status !== 'active' || ! $this->accepting_submissions) {
            return false;
        }

        return ! $this->offer_deadline || now()->lte($this->offer_deadline);
    }
}
