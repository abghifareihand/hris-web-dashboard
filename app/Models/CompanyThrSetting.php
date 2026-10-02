<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyThrSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'is_active',
        'min_months_tenure',
        'full_thr_months_tenure',
        'include_fixed_allowance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'include_fixed_allowance' => 'boolean',
        'min_months_tenure' => 'integer',
        'full_thr_months_tenure' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
