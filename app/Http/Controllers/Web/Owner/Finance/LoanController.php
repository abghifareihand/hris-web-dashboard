<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function pending(Request $request)
    {
        $company = Auth::user()->company;
        if (!$company) {
            return redirect()->back()->with('error', 'Perusahaan tidak ditemukan.');
        }

        $query = Loan::with(['employee:id,name,nip,basic_salary,branch_id,division_id', 'employee.branch:id,name', 'employee.division:id,name'])
            ->where('company_id', $company->id)
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $pendingLoans = $query->latest('date')->paginate(10)->withQueryString();

        return Inertia::render('Owner/Finance/Loans/Pending', [
            'pendingLoans' => $pendingLoans,
            'filters' => $request->only(['search']),
        ]);
    }

    public function approve($id)
    {
        $company = Auth::user()->company;
        $loan = Loan::where('company_id', $company->id)->findOrFail($id);

        $loan->status = 'approved';
        $loan->save();

        if ($loan->installments()->count() === 0) {
            $installmentAmount = $loan->amount / $loan->tenor;
            $startDate = Carbon::parse($loan->date)->startOfMonth();
            $loanSetting = $company->loanSetting()->first();
            $dueDay = ($loanSetting && $loanSetting->due_date_day) ? $loanSetting->due_date_day : 1;

            for ($i = 1; $i <= $loan->tenor; $i++) {
                $monthDate = $startDate->copy()->addMonths($i);
                $actualDay = min($dueDay, $monthDate->daysInMonth);
                $dueDate = $monthDate->copy()->day($actualDay);

                LoanInstallment::create([
                    'loan_id' => $loan->id,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $installmentAmount,
                    'status' => 'unpaid',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Pengajuan pinjaman berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $company = Auth::user()->company;
        $loan = Loan::where('company_id', $company->id)->findOrFail($id);

        $loan->status = 'rejected';
        $loan->save();

        return redirect()->back()->with('success', 'Pengajuan pinjaman berhasil ditolak.');
    }

    public function index(Request $request)
    {
        $company = Auth::user()->company;
        if (!$company) {
            return redirect()->back()->with('error', 'Perusahaan tidak ditemukan.');
        }

        $query = Loan::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.branch:id,name',
            'employee.division:id,name',
            'installments'
        ])->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['approved', 'paid']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $loans = $query->latest('date')->paginate(10)->withQueryString();

        $stats = [
            'total_active_loan' => Loan::where('company_id', $company->id)->where('status', 'approved')->sum('amount'),
            'total_paid_loan' => Loan::where('company_id', $company->id)->where('status', 'paid')->sum('amount'),
            'pending_count' => Loan::where('company_id', $company->id)->where('status', 'pending')->count(),
        ];

        return Inertia::render('Owner/Finance/Loans/Index', [
            'loans' => $loans,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function create()
    {
        $company = Auth::user()->company;
        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'nip', 'basic_salary')
            ->orderBy('name')
            ->get();
        
        $loanSetting = $company->loanSetting()->first();

        return Inertia::render('Owner/Finance/Loans/Create', [
            'employees' => $employees,
            'loanSetting' => $loanSetting,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'tenor' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string',
        ]);

        $company = Auth::user()->company;
        $employee = Employee::where('company_id', $company->id)->findOrFail($request->employee_id);

        $amount = (float) $request->amount;
        $tenor = (int) $request->tenor;
        $installmentPerMonth = round($amount / $tenor);
        $basicSalary = (float) ($employee->basic_salary ?? 0);

        $maxAllowedInstallment = round($basicSalary * 0.35);
        $ratioPercentage = $basicSalary > 0 ? (float) round(($installmentPerMonth / $basicSalary) * 100, 1) : 0;

        if ($basicSalary > 0 && $installmentPerMonth > $maxAllowedInstallment) {
            $recommendedMinTenor = ceil($amount / $maxAllowedInstallment);
            $recommendedMaxAmount = floor($maxAllowedInstallment * $tenor);
            $message = "Estimasi cicilan kasbon Rp " . number_format($installmentPerMonth, 0, ',', '.') . "/bln ({$ratioPercentage}% dari gaji pokok) melebihi batas kebijakan 35% dari gaji pokok (Maks. Rp " . number_format($maxAllowedInstallment, 0, ',', '.') . "/bln).";

            return redirect()->back()->withInput()->withErrors([
                'amount' => "{$message} Solusi: Ubah tenor minimal menjadi {$recommendedMinTenor} bulan atau kurangi nominal pinjaman maksimal Rp " . number_format($recommendedMaxAmount, 0, ',', '.') . "."
            ]);
        }

        $loanSetting = $company->loanSetting()->first();
        if ($loanSetting) {
            if ($loanSetting->max_loan_amount && $request->amount > $loanSetting->max_loan_amount) {
                return redirect()->back()->withInput()->withErrors([
                    'amount' => 'Nominal pinjaman melebihi batas maksimal perusahaan (Maksimal: Rp ' . number_format($loanSetting->max_loan_amount, 0, ',', '.') . ').'
                ]);
            }
            if ($loanSetting->max_tenor_months && $request->tenor > $loanSetting->max_tenor_months) {
                return redirect()->back()->withInput()->withErrors([
                    'tenor' => 'Durasi tenor melebihi batas maksimal perusahaan (Maksimal: ' . $loanSetting->max_tenor_months . ' bulan).'
                ]);
            }
        }

        $loan = Loan::create([
            'company_id' => $company->id,
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'tenor' => $request->tenor,
            'description' => $request->description,
            'status' => 'approved',
        ]);

        $installmentAmount = $loan->amount / $loan->tenor;
        $startDate = Carbon::parse($loan->date)->startOfMonth();
        $dueDay = ($loanSetting && $loanSetting->due_date_day) ? $loanSetting->due_date_day : 1;

        for ($i = 1; $i <= $loan->tenor; $i++) {
            $monthDate = $startDate->copy()->addMonths($i);
            $actualDay = min($dueDay, $monthDate->daysInMonth);
            $dueDate = $monthDate->copy()->day($actualDay);

            LoanInstallment::create([
                'loan_id' => $loan->id,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $installmentAmount,
                'status' => 'unpaid',
            ]);
        }

        return redirect()->route('owner.finance.loans.index')->with('success', 'Pinjaman berhasil dibuat.');
    }

    public function show($id)
    {
        $company = Auth::user()->company;
        $loan = Loan::with([
            'employee:id,name,nip,basic_salary,branch_id,division_id,position_id',
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
            'installments' => function ($q) {
                $q->orderBy('due_date', 'asc');
            }
        ])->where('company_id', $company->id)->findOrFail($id);

        return Inertia::render('Owner/Finance/Loans/Show', [
            'loan' => $loan,
        ]);
    }

    public function destroy($id)
    {
        $company = Auth::user()->company;
        $loan = Loan::where('company_id', $company->id)->findOrFail($id);

        $hasPaidInstallment = $loan->installments()->where('status', 'paid')->exists();
        if ($hasPaidInstallment) {
            return redirect()->back()->with('error', 'Pinjaman yang sudah memiliki riwayat pembayaran tidak dapat dihapus.');
        }

        $installmentIds = $loan->installments()->pluck('id');
        $isAttachedToPayroll = \App\Models\PayrollItem::whereIn('loan_installment_id', $installmentIds)->exists();
        if ($isAttachedToPayroll) {
            return redirect()->back()->with('error', 'Pinjaman ini memiliki cicilan yang sedang diproses dalam penggajian dan tidak dapat dihapus.');
        }

        $loan->delete();

        return redirect()->route('owner.finance.loans.index')->with('success', 'Data pinjaman berhasil dihapus.');
    }

    public function updateInstallment(Request $request, $id, $installmentId)
    {
        $company = Auth::user()->company;
        $loan = Loan::where('company_id', $company->id)->findOrFail($id);
        
        $installment = LoanInstallment::where('loan_id', $loan->id)->findOrFail($installmentId);
        
        $installment->status = $installment->status === 'paid' ? 'unpaid' : 'paid';
        $installment->save();

        $unpaidCount = $loan->installments()->where('status', 'unpaid')->count();
        $loan->status = $unpaidCount === 0 ? 'paid' : 'approved';
        $loan->save();

        return redirect()->back()->with('success', 'Status cicilan berhasil diperbarui.');
    }
}
