<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GisFairTrackingVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'token', 'campaign_id', 'tracking_link_id', 'lead_id', 'ip_hash', 'user_agent_hash',
        'referrer', 'destination_url', 'query_parameters', 'visited_at', 'converted_at',
    ];

    protected $casts = [
        'query_parameters' => 'array',
        'visited_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(GisFairCampaign::class, 'campaign_id');
    }

    public function trackingLink()
    {
        return $this->belongsTo(GisFairTrackingLink::class, 'tracking_link_id');
    }
}
