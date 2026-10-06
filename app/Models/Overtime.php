<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'status',
        'reject_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'duration_hours',
        'notes',
        'reason',
    ];

    public function getDurationHoursAttribute()
    {
        return $this->duration_minutes ? round($this->duration_minutes / 60, 1) : 0;
    }

    public function getNotesAttribute()
    {
        return $this->description;
    }

    public function getReasonAttribute()
    {
        return $this->description;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
