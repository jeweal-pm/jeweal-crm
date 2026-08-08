<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'first_name', 'last_name', 'company_name', 'source_type', 'source_id',
        'subscription_status', 'preferences', 'unsubscribe_token_hash', 'consent_source',
        'lawful_basis', 'consent_version', 'privacy_notice_version', 'subscribed_at',
        'unsubscribed_at', 'last_sent_at', 'last_opened_at', 'last_clicked_at',
    ];

    protected $casts = [
        'preferences' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: $this->email;
    }

    public function canReceiveMarketing(string $category = 'all_marketing'): bool
    {
        if ($this->subscription_status !== 'subscribed') {
            return false;
        }

        return ! EmailSuppressionList::query()
            ->where('email', $this->email)
            ->whereIn('category', [$category, 'all_marketing'])
            ->exists();
    }

    public function messages()
    {
        return $this->hasMany(EmailMessage::class);
    }
}
