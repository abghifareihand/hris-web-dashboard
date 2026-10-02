<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThrItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'thr_id',
        'employee_id',
        'tenure_months',
        'basis_amount',
        'prorate_multiplier',
        'thr_amount',
        'tax_amount',
        'net_amount',
    ];

    protected $casts = [
        'tenure_months' => 'integer',
        'basis_amount' => 'integer',
        'prorate_multiplier' => 'decimal:2',
        'thr_amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
    ];

    public function thr()
    {
        return $this->belongsTo(Thr::class, 'thr_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
