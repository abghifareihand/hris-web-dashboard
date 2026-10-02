<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBpjsKesehatan extends Model
{
    use HasFactory;

    protected $table = 'company_bpjs_kesehatan';

    protected $fillable = [
        'company_id',
        'is_active',
        'company_percent',
        'employee_percent',
        'minimum_wage',
        'maximum_wage',
        'effective_year',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
