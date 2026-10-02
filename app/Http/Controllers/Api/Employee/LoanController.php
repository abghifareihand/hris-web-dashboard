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
                'monthly_installment' => $loan->tenor > 0 ? (int) round($loan->amount / $loan->tenor) : 0,
                'paid_amount' => (int) $paidAmount,
                'remaining_amount' => (int) $remainingAmount,
                'paid_installments_count' => $paidCount,
                'total_installments_count' => $totalCount,
                'description' => $loan->description,
                'status' => $loan->status, // 'pending', 'approved', 'rejected', 'paid'
                'created_at' => $loan->created_at->format('Y-m-d H:i:s'),

                // Detailed installments schedule included directly
                'installments' => $loan->installments->map(function ($inst, $index) {
                    return [
                        'id' => $inst->id,
                        'installment_number' => $index + 1,
                        'due_date' => Carbon::parse($inst->due_date)->format('Y-m-d'),
                        'amount' => (int) $inst->amount,
                        'status' => $inst->status, // 'unpaid', 'paid'
                        'updated_at' => $inst->updated_at->format('Y-m-d H:i:s'),
                    ];
                })->values(),
            ];
        });

        return $this->paginatedResponse($loans, 'Daftar pinjaman berhasil diambil.');
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
            'tenor.min' => 'Durasi tenor minimal 1 bulan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $amount = (float) $request->amount;
        $tenor = (int) $request->tenor;
        $installmentPerMonth = round($amount / $tenor);
        $basicSalary = (float) ($employee->basic_salary ?? 0);
        $maxAllowedInstallment = round($basicSalary * 0.35);

        // Batas maksimal cicilan: 35% dari gaji pokok karyawan
        if ($basicSalary > 0 && $installmentPerMonth > $maxAllowedInstallment) {
            $ratioPercentage = round(($installmentPerMonth / $basicSalary) * 100, 1);
            $recommendedMinTenor = ceil($amount / $maxAllowedInstallment);
            $recommendedMaxAmount = floor($maxAllowedInstallment * $tenor);

            return response()->json([
                'status' => false,
                'message' => "Estimasi cicilan kasbon Rp " . number_format($installmentPerMonth, 0, ',', '.') . "/bln ({$ratioPercentage}% dari gaji pokok) melebihi batas aman kebijakan 35% (Maks. Rp " . number_format($maxAllowedInstallment, 0, ',', '.') . "/bln). Saran: Ubah tenor minimal menjadi {$recommendedMinTenor} bulan atau kurangi nominal pinjaman maksimal Rp " . number_format($recommendedMaxAmount, 0, ',', '.') . ".",
            ], 422);
        }

        $company = $employee->company;
        $loanSetting = $company ? $company->loanSetting : null;

        // Check against company loan settings if configured
        if ($loanSetting) {
            if ($loanSetting->max_loan_amount && $amount > $loanSetting->max_loan_amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nominal pinjaman melebihi batas maksimal perusahaan (Maksimal: Rp ' . number_format($loanSetting->max_loan_amount, 0, ',', '.') . ').'
                ], 422);
            }

            if ($loanSetting->max_tenor_months && $tenor > $loanSetting->max_tenor_months) {
                return response()->json([
                    'status' => false,
                    'message' => 'Durasi tenor cicilan melebihi batas maksimal perusahaan (Maksimal: ' . $loanSetting->max_tenor_months . ' bulan).'
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

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan pinjaman berhasil dikirim. Menunggu persetujuan.',
        ], 201);
    }
}

