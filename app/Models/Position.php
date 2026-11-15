<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Hak akses default bawaan sistem untuk staf biasa
     */
    public static function defaultPermissions(): array
    {
        return [
            'swap_personal' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'swap_team' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'leaves' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'agenda' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'overtimes' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'reimbursements' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'loans' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => true],
            'payslips' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'feedbacks' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => false],
            'daily_reports' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
