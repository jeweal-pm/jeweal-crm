<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAutomationConfig extends Model
{
    protected $fillable = [
        'enquiry_type', 'customer_enabled', 'customer_template_id', 'customer_delay_seconds',
        'internal_enabled', 'internal_template_id', 'internal_to', 'internal_cc', 'internal_bcc',
        'internal_assignment_mode', 'reminder_after_minutes', 'welcome_enabled',
        'welcome_template_id', 'welcome_delay_seconds', 'metadata',
    ];

    protected $casts = [
        'customer_enabled' => 'boolean', 'internal_enabled' => 'boolean', 'welcome_enabled' => 'boolean',
        'internal_to' => 'array', 'internal_cc' => 'array', 'internal_bcc' => 'array', 'metadata' => 'array',
    ];

    public function customerTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'customer_template_id');
    }

    public function internalTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'internal_template_id');
    }

    public function welcomeTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'welcome_template_id');
    }
}
