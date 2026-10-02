<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thr extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'year',
        'holiday_name',
        'payment_date',
        'branch_id',
        'status',
        'total_employees',
        'total_amount',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'year' => 'integer',
        'total_employees' => 'integer',
        'total_amount' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(ThrItem::class, 'thr_id');
    }
}
