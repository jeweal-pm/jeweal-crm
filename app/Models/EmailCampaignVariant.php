<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaignVariant extends Model
{
    protected $fillable = ['email_campaign_id', 'variant_key', 'email_template_id', 'subject', 'sender_name', 'allocation', 'success_metric', 'minimum_sample_size'];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
