<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['name'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? $this->attributes['name_company'] ?? null;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function leaveCategories(): HasMany
    {
        return $this->hasMany(LeaveCategory::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function attendanceSetting()
    {
        return $this->hasOne(AttendanceSetting::class);
    }

    public function bpjsKesehatan()
    {
        return $this->hasOne(CompanyBpjsKesehatan::class);
    }

    public function bpjsKetenagakerjaan()
    {
        return $this->hasOne(CompanyBpjsKetenagakerjaan::class);
    }

    public function thrSetting()
    {
        return $this->hasOne(CompanyThrSetting::class);
    }

    public function thrs()
    {
        return $this->hasMany(Thr::class);
    }

    public function loanSetting()
    {
        return $this->hasOne(CompanyLoanSetting::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}
