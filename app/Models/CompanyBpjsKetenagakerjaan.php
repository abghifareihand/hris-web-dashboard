<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBpjsKetenagakerjaan extends Model
{
    use HasFactory;

    protected $table = 'company_bpjs_ketenagakerjaan';

    protected $fillable = [
        'company_id',
        'jht_active',
        'jht_company_percent',
        'jht_employee_percent',
        'jkk_active',
        'jkk_risk_level',
        'jkk_percent',
        'jkm_active',
        'jkm_percent',
        'jp_active',
        'jp_company_percent',
        'jp_employee_percent',
        'jp_maximum_wage',
        'effective_year',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
