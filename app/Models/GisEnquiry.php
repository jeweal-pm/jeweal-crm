<?php

namespace App\Models;

use App\Contracts\Enquirable as EnquirableContract;
use App\Models\Concerns\HasEnquiryWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GisEnquiry extends Model implements EnquirableContract
{
    use HasEnquiryWorkflow;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'inquiry',
        'message',
        'privacy_policy_accepted',
        'marketing_consent',
    ];

    protected $attributes = [
        'status' => 'lead_mql',
        'spam_status' => 'clean',
        'spam_score' => 0,
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
        'privacy_policy_accepted' => 'boolean',
        'marketing_consent' => 'boolean',
        'counts_for_sale_kpi' => 'boolean',
        'spam_reasons' => 'array',
        'spam_checked_at' => 'datetime',
        'spam_reviewed_at' => 'datetime',
    ];
}
