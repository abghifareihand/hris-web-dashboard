<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = [
        'phone',
        'gender',
        'birth_date',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function getPhoneAttribute()
    {
        return $this->user?->phone;
    }

    public function getGenderAttribute()
    {
        return $this->user?->gender;
    }

    public function getBirthDateAttribute()
    {
        return $this->user?->birth_date;
    }

    public function getAddressAttribute()
    {
        return $this->user?->address;
    }
}
