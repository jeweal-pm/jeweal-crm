<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSequenceStep extends Model
{
    protected $fillable = [
        'email_sequence_template_id', 'step_number', 'email_template_id', 'delay_value', 'delay_unit',
        'timezone', 'business_days_only', 'conditions', 'skip_conditions', 'actions',
    ];

    protected $casts = [
        'business_days_only' => 'boolean', 'conditions' => 'array', 'skip_conditions' => 'array', 'actions' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
