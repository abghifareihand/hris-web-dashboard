<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'date',
        'shift_id',
        'clock_in_time',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_photo',
        'clock_out_time',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_photo',
        'late_minutes',
        'early_leaving_minutes',
        'total_work_minutes',
        'total_violation_minutes',
        'attendance_status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function getStatusLabelAttribute()
    {
        $code = $this->attendance_status;
        if (!$code) return '-';

        if ($code === 'clocked_in') return 'Belum Pulang';
        if ($code === 'leave') return 'Cuti / Izin';
        if ($code === 'alpha') return 'Alpha';
        if ($code === 'present') return 'Hadir';

        // Get setting from DB (cache it if possible, but simple query for now)
        $setting = AttendanceSetting::where('company_id', $this->company_id)->first();
        if (!$setting) {
            $defaults = [
                'very_good' => 'Sangat Baik',
                'good' => 'Baik',
                'fair' => 'Cukup',
                'poor' => 'Kurang',
            ];
            return $defaults[$code] ?? $code;
        }

        switch ($code) {
            case 'very_good': return $setting->status_very_good;
            case 'good': return $setting->status_good;
            case 'fair': return $setting->status_fair;
            case 'poor': return $setting->status_poor;
            default: return $code;
        }
    }

    public function getStatusColorAttribute()
    {
        $code = $this->attendance_status;
        switch ($code) {
            case 'very_good': return 'green';
            case 'good': return 'blue';
            case 'fair': return 'yellow';
            case 'poor': return 'red';
            case 'clocked_in': return 'slate';
            case 'leave': return 'purple';
            case 'alpha': return 'red';
            case 'present': return 'green';
            default: return 'gray';
        }
    }
}
