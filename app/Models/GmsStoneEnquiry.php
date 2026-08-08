<?php

namespace App\Models;

use App\Models\Concerns\HasEnquiryWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GmsStoneEnquiry extends Model
{
    use HasEnquiryWorkflow;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'country_code',
        'account_type',
        'status',
        'business_name',
        'company_name',
        'tax_id',
        'mailing_name',
        'website',
        'office_type',
        'branch_code',
        'address',
        'country',
        'city',
        'province',
        'postcode',
        'contact_name',
        'contact_email',
            'contact_phone',
            'is_seen',
            'is_approved',
            'privacy_policy_accepted',
            'terms_conditions_accepted',
        ];

    protected $attributes = [
        'status' => 'lead_mql',
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'is_approved' => 'boolean',
        'privacy_policy_accepted' => 'boolean',
        'terms_conditions_accepted' => 'boolean',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
        'counts_for_sale_kpi' => 'boolean',
    ];
}
