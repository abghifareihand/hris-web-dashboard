<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    /**
     * Get list of loans for the authenticated employee,
     * including full installment schedule details directly.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $query = Loan::with(['installments' => function ($q) {
            $q->orderBy('due_date', 'asc');
        }])
        ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->latest('date')->latest('id')->paginate($this->getPerPage($request));

        $loans->getCollection()->transform(function ($loan) {
            $paidAmount = $loan->installments->where('status', 'paid')->sum('amount');
            $remainingAmount = $loan->installments->where('status', 'unpaid')->sum('amount');
            $paidCount = $loan->installments->where('status', 'paid')->count();
            $totalCount = $loan->installments->count();

            return [
                'id' => $loan->id,
                'date' => Carbon::parse($loan->date)->format('Y-m-d'),
                'amount' => (int) $loan->amount,
                'tenor' => (int) $loan->tenor,
                'tenor_unit' => $loan->tenor_unit,
                'tenor_label' => $loan->tenor_label,
                'installment_per_period' => $loan->tenor > 0 ? (int) round($loan->amount / $loan->tenor) : 0,
                'monthly_installment' => $loan->tenor > 0 ? (int) round($loan->amount / $loan->tenor) : 0,
                'paid_amount' => (int) $paidAmount,
                'remaining_amount' => (int) $remainingAmount,
                'paid_installments_count' => $paidCount,
                'total_installments_count' => $totalCount,
                'description' => $loan->description,
                'status' => $loan->status, // 'pending', 'approved', 'rejected', 'paid'
                'created_at' => Carbon::parse($loan->created_at ?? now())->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($loan->updated_at ?? $loan->created_at ?? now())->format('Y-m-d H:i:s'),

                // Detailed installments schedule included directly
                'installments' => $loan->installments->map(function ($inst, $index) {
                    return [
                        'id' => $inst->id,
                        'installment_number' => $index + 1,
                        'due_date' => Carbon::parse($inst->due_date)->format('Y-m-d'),
                        'amount' => (int) $inst->amount,
                        'status' => $inst->status, // 'unpaid', 'paid'
                        'created_at' => Carbon::parse($inst->created_at ?? now())->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::parse($inst->updated_at ?? $inst->created_at ?? now())->format('Y-m-d H:i:s'),
                    ];
                })->values(),
            ];
        });

        return $this->paginatedResponse($loans, 'Daftar pinjaman berhasil diambil.');
    }

    /**
     * Get loan settings, policies and employee limits.
     */
    public function settings(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $company = $employee->company;
        $loanSetting = $company ? $company->loanSetting : null;

        $basicSalary = (int) ($employee->basic_salary ?? 0);
        $maxPercentage = 35;
        $maxInstallmentPerCycle = (int) round($basicSalary * ($maxPercentage / 100));

        $cycle = $employee->payroll_cycle ?? 'monthly';
        $cycleUnit = match ($cycle) {
            'daily' => 'Hari',
            'weekly' => 'Minggu',
            default => 'Bulan',
        };

        // Batas maksimal tenor per siklus dari pengaturan perusahaan
        $maxTenor = match ($cycle) {
            'daily' => ($loanSetting && $loanSetting->max_tenor_days) ? (int) $loanSetting->max_tenor_days : 14,
            'weekly' => ($loanSetting && $loanSetting->max_tenor_weeks) ? (int) $loanSetting->max_tenor_weeks : 4,
            default => ($loanSetting && $loanSetting->max_tenor_months) ? (int) $loanSetting->max_tenor_months : 12,
        };

        // Buat opsi tenor berurutan rapi: [1, 2, 3, ... $maxTenor]
        $maxTenor = max(1, min(60, $maxTenor));
        $tenorOptions = range(1, $maxTenor);

        // Batas maksimal nominal pinjaman per siklus
        $maxLoanAmount = match ($cycle) {
            'daily' => $loanSetting?->max_loan_amount_daily ?? $loanSetting?->max_loan_amount,
            'weekly' => $loanSetting?->max_loan_amount_weekly ?? $loanSetting?->max_loan_amount,
            default => $loanSetting?->max_loan_amount,
        };

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan pinjaman berhasil diambil.',
            'data' => [
                'payroll_cycle' => $cycle,
                'cycle_unit' => $cycleUnit,
                'basic_salary' => $basicSalary,
                'max_percentage' => $maxPercentage,
                'max_loan_amount' => $maxLoanAmount !== null ? (int) $maxLoanAmount : null,
                'max_installment_per_cycle' => $maxInstallmentPerCycle,
                'max_monthly_installment' => $maxInstallmentPerCycle,
                'max_tenor' => $maxTenor,
                'tenor_options' => $tenorOptions,
            ],
        ]);
    }

    /**
     * Submit a new loan request from employee.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50000',
            'tenor' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string|max:500',
            'date' => 'nullable|date',
        ], [
            'amount.required' => 'Nominal pinjaman wajib diisi.',
            'amount.min' => 'Nominal pinjaman minimal Rp 50.000.',
            'tenor.required' => 'Durasi tenor cicilan wajib diisi.',
            'tenor.min' => 'Durasi tenor minimal 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $cycle = $employee->payroll_cycle ?? 'monthly';
        $cycleUnit = match ($cycle) {
            'daily' => 'hari',
            'weekly' => 'minggu',
            default => 'bulan',
        };

        $amount = (float) $request->amount;
        $tenor = (int) $request->tenor;
        $installmentPerCycle = round($amount / $tenor);
        $basicSalary = (float) ($employee->basic_salary ?? 0);
        $maxAllowedInstallment = round($basicSalary * 0.35);

        // Batas maksimal cicilan: 35% dari gaji pokok karyawan per siklus
        if ($basicSalary > 0 && $installmentPerCycle > $maxAllowedInstallment) {
            $ratioPercentage = round(($installmentPerCycle / $basicSalary) * 100, 1);
            $recommendedMinTenor = ceil($amount / $maxAllowedInstallment);
            $recommendedMaxAmount = floor($maxAllowedInstallment * $tenor);

            return response()->json([
                'status' => false,
                'message' => "Estimasi cicilan kasbon Rp " . number_format($installmentPerCycle, 0, ',', '.') . "/{$cycleUnit} ({$ratioPercentage}% dari gaji {$cycleUnit}an) melebihi batas aman kebijakan 35% (Maks. Rp " . number_format($maxAllowedInstallment, 0, ',', '.') . "/{$cycleUnit}). Saran: Ubah tenor minimal menjadi {$recommendedMinTenor} {$cycleUnit} atau kurangi nominal pinjaman maksimal Rp " . number_format($recommendedMaxAmount, 0, ',', '.') . ".",
            ], 422);
        }

        $company = $employee->company;
        $loanSetting = $company ? $company->loanSetting : null;

        // Check against company loan settings if configured
        if ($loanSetting) {
            $effectiveMaxLoanAmount = match ($cycle) {
                'daily' => $loanSetting->max_loan_amount_daily ?? $loanSetting->max_loan_amount,
                'weekly' => $loanSetting->max_loan_amount_weekly ?? $loanSetting->max_loan_amount,
                default => $loanSetting->max_loan_amount,
            };

            if ($effectiveMaxLoanAmount && $amount > $effectiveMaxLoanAmount) {
                return response()->json([
                    'status' => false,
                    'message' => "Nominal pinjaman melebihi batas maksimal kebijakan perusahaan untuk karyawan {$cycleUnit}an (Maksimal: Rp " . number_format($effectiveMaxLoanAmount, 0, ',', '.') . ').'
                ], 422);
            }

            $effectiveMaxTenor = match ($cycle) {
                'daily' => $loanSetting->max_tenor_days,
                'weekly' => $loanSetting->max_tenor_weeks,
                default => $loanSetting->max_tenor_months,
            };

            if ($effectiveMaxTenor && $tenor > $effectiveMaxTenor) {
                return response()->json([
                    'status' => false,
                    'message' => "Durasi tenor cicilan melebihi batas maksimal kebijakan perusahaan (Maksimal: {$effectiveMaxTenor} {$cycleUnit})."
                ], 422);
            }
        }


        $loan = Loan::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->input('date', date('Y-m-d')),
            'amount' => $request->amount,
            'tenor' => $request->tenor,
            'description' => $request->description,
            'status' => 'pending', // Pending owner approval
        ]);

        $loan->load('employee');
        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new \App\Notifications\NewEmployeeLoanPendingRequest($loan));
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan pinjaman berhasil dikirim. Menunggu persetujuan.',
        ], 201);
    }

    /**
     * Cancel a pending loan request by the employee.
     */
    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $loan = Loan::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$loan) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan pinjaman tidak ditemukan.'
            ], 404);
        }

        if ($loan->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya pengajuan dengan status menunggu (pending) yang dapat dibatalkan.'
            ], 422);
        }

        $loan->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan pinjaman berhasil dibatalkan.'
        ]);
    }
}

