<?php

namespace App\Models;

use App\Contracts\Enquirable as EnquirableContract;
use App\Models\Concerns\HasEnquiryWorkflow;
use App\Casts\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model implements EnquirableContract
{
    use HasEnquiryWorkflow;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'business_type',
        'email',
        'country',
        'phone',
        'company',
        'company_website',
        'description',
        'interest_in',
    ];

    protected $attributes = [
        'status' => 'lead_mql',
        'spam_status' => 'clean',
        'spam_score' => 0,
    ];

    protected $casts = [
        'business_type' => Json::class,
        'interest_in' => Json::class,
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
        'counts_for_sale_kpi' => 'boolean',
        'spam_reasons' => 'array',
        'spam_checked_at' => 'datetime',
        'spam_reviewed_at' => 'datetime',
    ];
}
