<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'date',
        'amount',
        'tenor',
        'description',
        'status',
    ];

    protected $appends = [
        'tenor_unit',
        'tenor_label',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function getTenorUnitAttribute(): string
    {
        $cycle = $this->employee->payroll_cycle ?? 'monthly';
        return match ($cycle) {
            'daily' => 'Hari',
            'weekly' => 'Minggu',
            default => 'Bulan',
        };
    }

    public function getTenorLabelAttribute(): string
    {
        return $this->tenor . ' ' . $this->tenor_unit;
    }

    /**
     * Generate loan installment schedules based on employee's payroll cycle.
     */
    public function generateInstallments(): void
    {
        if ($this->installments()->count() > 0) {
            return;
        }

        $tenor = (int) ($this->tenor ?: 1);
        $installmentAmount = round($this->amount / $tenor);
        $employee = $this->employee;
        $cycle = $employee->payroll_cycle ?? 'monthly';
        $company = $this->company;
        $loanSetting = $company ? $company->loanSetting()->first() : null;
        $loanDate = Carbon::parse($this->date);

        if ($cycle === 'daily') {
            // Harian: Cicilan jatuh tempo setiap hari berikutnya
            for ($i = 1; $i <= $tenor; $i++) {
                $dueDate = $loanDate->copy()->addDays($i);
                LoanInstallment::create([
                    'loan_id' => $this->id,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'unpaid',
                ]);
            }
        } elseif ($cycle === 'weekly') {
            // Mingguan: Cicilan jatuh tempo setiap hari SABTU
            // Jika tanggal pengajuan sudah Sabtu, cicilan pertama adalah Sabtu berikutnya.
            $firstSaturday = $loanDate->isSaturday() 
                ? $loanDate->copy()->addWeek() 
                : $loanDate->copy()->next(Carbon::SATURDAY);

            for ($i = 0; $i < $tenor; $i++) {
                $dueDate = $firstSaturday->copy()->addWeeks($i);
                LoanInstallment::create([
                    'loan_id' => $this->id,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'unpaid',
                ]);
            }
        } else {
            // Bulanan: Cicilan jatuh tempo bulanan pada tanggal due_date_day perusahaan
            $startDate = $loanDate->copy()->startOfMonth();
            $dueDay = ($loanSetting && $loanSetting->due_date_day) ? $loanSetting->due_date_day : 1;

            for ($i = 1; $i <= $tenor; $i++) {
                $monthDate = $startDate->copy()->addMonths($i);
                $actualDay = min($dueDay, $monthDate->daysInMonth);
                $dueDate = $monthDate->copy()->day($actualDay);

                LoanInstallment::create([
                    'loan_id' => $this->id,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'unpaid',
                ]);
            }
        }
    }
}
