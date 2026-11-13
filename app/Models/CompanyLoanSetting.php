<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLoanSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'max_loan_amount',
        'max_loan_amount_weekly',
        'max_loan_amount_daily',
        'max_tenor_months',
        'max_tenor_weeks',
        'max_tenor_days',
        'due_date_day',
    ];

    protected $casts = [
        'max_loan_amount' => 'integer',
        'max_loan_amount_weekly' => 'integer',
        'max_loan_amount_daily' => 'integer',
        'max_tenor_months' => 'integer',
        'max_tenor_weeks' => 'integer',
        'max_tenor_days' => 'integer',
        'due_date_day' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
