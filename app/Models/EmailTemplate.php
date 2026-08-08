<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'email_type', 'category', 'subject', 'preview_text', 'html_content',
        'plain_text_content', 'sender_name', 'sender_email', 'reply_to_email', 'language',
        'status', 'version', 'variables', 'created_by', 'updated_by',
    ];

    protected $casts = ['variables' => 'array'];

    public function versions()
    {
        return $this->hasMany(EmailTemplateVersion::class);
    }
}
