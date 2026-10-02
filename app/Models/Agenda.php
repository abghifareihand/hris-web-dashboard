<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'notes',
        'is_priority',
    ];

    protected $casts = [
        'date' => 'date',
        'is_priority' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
