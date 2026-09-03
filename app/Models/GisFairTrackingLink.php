<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GisFairTrackingLink extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'campaign_id', 'name', 'code', 'destination_url', 'expired_redirect_url', 'source', 'medium', 'content',
        'is_active', 'expires_at', 'click_count', 'lead_count', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'click_count' => 'integer',
        'lead_count' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(GisFairCampaign::class, 'campaign_id');
    }

    public function visits()
    {
        return $this->hasMany(GisFairTrackingVisit::class, 'tracking_link_id');
    }

    public function isAvailable(): bool
    {
        return $this->is_active && (! $this->expires_at || now()->lte($this->expires_at));
    }

    public function expiredRedirectUrl(): string
    {
        return $this->expired_redirect_url ?: 'https://jeweal.com';
    }
}
