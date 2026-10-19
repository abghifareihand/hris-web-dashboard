<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'default_payroll_cycle',
        'monthly_working_days',
        'weekly_working_days',
        'notes',
    ];

    protected $casts = [
        'monthly_working_days' => 'integer',
        'weekly_working_days' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
