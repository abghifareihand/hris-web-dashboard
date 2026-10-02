<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'company_id',
        'late_tolerance_minutes',
        'early_clock_in_minutes',
        'camera_mode',
        'status_very_good',
        'status_good',
        'status_fair',
        'status_poor'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
