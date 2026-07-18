<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GmsStoneEnquiry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'country_code',
        'account_type',
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
        ];

    protected $casts = [
        'is_seen' => 'boolean',
        'is_approved' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasCrmPermission('enquiry.view.all')) {
            return $query;
        }

        return $query->where($this->getTable().'.assigned_to', $user->id);
    }

    public function assignTo(User $target, User $actor): void
    {
        $this->forceFill([
            'assigned_to' => $target->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ])->save();
    }
}
