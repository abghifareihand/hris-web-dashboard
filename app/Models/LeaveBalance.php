<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_category_id',
        'year',
        'quota',
        'used',
    ];

    protected $appends = ['balance'];

    public function getBalanceAttribute(): int
    {
        $quota = $this->quota ?? 0;
        $used = $this->used ?? 0;
        return max(0, (int)$quota - (int)$used);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveCategory()
    {
        return $this->belongsTo(LeaveCategory::class);
    }
}
