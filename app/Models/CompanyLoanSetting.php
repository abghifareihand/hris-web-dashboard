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
        'max_tenor_months',
        'due_date_day',
    ];

    protected $casts = [
        'max_loan_amount' => 'integer',
        'max_tenor_months' => 'integer',
        'due_date_day' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
