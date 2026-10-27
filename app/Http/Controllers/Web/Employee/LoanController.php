<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Loan;
use App\Notifications\NewEmployeeLoanPendingRequest;

class LoanController extends Controller
{
    /**
     * Display list of employee loans and installment status.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Loan::with(['installments' => function ($q) {
            $q->orderBy('due_date', 'asc');
        }])->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('reason', 'like', "%{$search}%");
        }

        $loans = $query->latest('date')->latest('id')->paginate(10)->withQueryString();

        // Calculate statistics
        $allLoans = Loan::with('installments')->where('employee_id', $employee->id)->get();
        $totalActiveLoan = (float) $allLoans->whereIn('status', ['approved', 'paid'])->sum('amount');

        $totalRemainingBalance = 0;
        foreach ($allLoans->where('status', 'approved') as $activeLoan) {
            $totalRemainingBalance += $activeLoan->installments->where('status', 'unpaid')->sum('amount');
        }

        $pendingCount = $allLoans->where('status', 'pending')->count();

        // Policy & limits context for form calculation
        $company = $employee->company;
        $loanSetting = $company ? $company->loanSetting : null;
        $basicSalary = (int) ($employee->basic_salary ?? 0);
        $maxPercentage = 35;
        $maxMonthlyInstallment = (int) round($basicSalary * ($maxPercentage / 100));

        $cycle = $employee->payroll_cycle ?? 'monthly';
        $cycleUnit = match ($cycle) {
            'daily' => 'Hari',
            'weekly' => 'Minggu',
            default => 'Bulan',
        };

        $defaultTenors = match ($cycle) {
            'daily' => [3, 5, 7, 10, 14, 21, 30],
            'weekly' => [1, 2, 3, 4, 6, 8, 12],
            default => [1, 2, 3, 4, 5, 6, 9, 12, 18, 24],
        };

        if ($cycle === 'monthly' && $loanSetting && $loanSetting->max_tenor_months) {
            $filteredTenors = array_values(array_filter($defaultTenors, fn($t) => $t <= $loanSetting->max_tenor_months));
            $tenorOptions = !empty($filteredTenors) ? $filteredTenors : [(int) $loanSetting->max_tenor_months];
        } else {
            $tenorOptions = $defaultTenors;
        }

        return Inertia::render('Employee/Loans/Index', [
            'loans' => $loans,
            'totalActiveLoan' => $totalActiveLoan,
            'totalRemainingBalance' => (float) $totalRemainingBalance,
            'pendingCount' => $pendingCount,
            'loanPolicy' => [
                'payrollCycle' => $cycle,
                'cycleUnit' => $cycleUnit,
                'basicSalary' => $basicSalary,
                'maxMonthlyInstallment' => $maxMonthlyInstallment,
                'maxLoanAmount' => $loanSetting?->max_loan_amount ? (float) $loanSetting->max_loan_amount : null,
                'maxTenorMonths' => $loanSetting?->max_tenor_months ?: 24,
                'tenorOptions' => $tenorOptions,
            ],
            'filters' => [
                'status' => $request->input('status', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Submit a new loan / kasbon request.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:50000',
            'tenor' => 'required|integer|min:1|max:60',
            'description' => 'required|string|max:1000',
        ], [
            'date.required' => 'Tanggal pengajuan pinjaman wajib diisi.',
            'amount.required' => 'Nominal pinjaman wajib diisi.',
            'amount.min' => 'Nominal pinjaman minimal Rp 50.000.',
            'tenor.required' => 'Pilih tenor cicilan yang diinginkan.',
            'description.required' => 'Keperluan pinjaman kasbon wajib diisi.',
        ]);

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

            return back()->withErrors([
                'amount' => "Estimasi cicilan kasbon Rp " . number_format($installmentPerCycle, 0, ',', '.') . "/{$cycleUnit} ({$ratioPercentage}% dari gaji {$cycleUnit}an) melebihi batas aman kebijakan 35% (Maks. Rp " . number_format($maxAllowedInstallment, 0, ',', '.') . "/{$cycleUnit}). Saran: Ubah tenor minimal menjadi {$recommendedMinTenor} {$cycleUnit} atau kurangi nominal pinjaman maksimal Rp " . number_format($recommendedMaxAmount, 0, ',', '.') . "."
            ]);
        }

        $company = $employee->company;
        $loanSetting = $company ? $company->loanSetting : null;

        if ($loanSetting) {
            if ($loanSetting->max_loan_amount && $amount > $loanSetting->max_loan_amount) {
                return back()->withErrors([
                    'amount' => 'Nominal pinjaman melebihi batas maksimal perusahaan (Maksimal: Rp ' . number_format($loanSetting->max_loan_amount, 0, ',', '.') . ').'
                ]);
            }

            if ($cycle === 'monthly' && $loanSetting->max_tenor_months && $tenor > $loanSetting->max_tenor_months) {
                return back()->withErrors([
                    'tenor' => 'Durasi tenor cicilan melebihi batas maksimal perusahaan (Maksimal: ' . $loanSetting->max_tenor_months . ' bulan).'
                ]);
            }
        }

        $loan = Loan::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $amount,
            'tenor' => $tenor,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new NewEmployeeLoanPendingRequest($loan));
        }

        return back()->with('success', 'Pengajuan pinjaman / kasbon berhasil dikirim. Menunggu persetujuan manajemen.');
    }

    /**
     * Cancel a pending loan request.
     */
    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $loan = Loan::where('employee_id', $employee->id)->findOrFail($id);

        if ($loan->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan yang berstatus menunggu yang dapat dibatalkan.');
        }

        $loan->delete();

        return back()->with('success', 'Pengajuan pinjaman kasbon berhasil dibatalkan.');
    }
}
