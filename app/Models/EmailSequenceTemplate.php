<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSequenceTemplate extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'status', 'version', 'priority', 'entry_conditions',
        'exit_conditions', 'timezone', 'created_by', 'updated_by',
    ];

    protected $casts = ['entry_conditions' => 'array', 'exit_conditions' => 'array'];

    public function steps()
    {
        return $this->hasMany(EmailSequenceStep::class)->orderBy('step_number');
    }
}
