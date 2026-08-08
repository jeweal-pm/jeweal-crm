<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_template_id', 'version', 'subject', 'preview_text', 'html_content',
        'plain_text_content', 'sender_name', 'sender_email', 'reply_to_email', 'variables',
        'created_by', 'created_at',
    ];

    protected $casts = ['variables' => 'array', 'created_at' => 'datetime'];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
