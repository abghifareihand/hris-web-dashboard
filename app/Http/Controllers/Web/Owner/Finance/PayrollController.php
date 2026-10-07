<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\WorkSchedule;
use App\Models\LoanInstallment;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PayrollSummaryExport;
use App\Exports\BankTransferExport;
use Maatwebsite\Excel\Facades\Excel;

class PayrollController extends Controller
{
    public function master(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Employee::where('company_id', $company->id)
            ->with(['user:id,avatar', 'branch:id,name', 'division:id,name', 'position:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->input('division_id'));
        }

        if ($request->filled('salary_status')) {
            if ($request->input('salary_status') === 'configured') {
                $query->whereNotNull('basic_salary')->where('basic_salary', '>', 0);
            } elseif ($request->input('salary_status') === 'unconfigured') {
                $query->where(function ($q) {
                    $q->whereNull('basic_salary')->orWhere('basic_salary', '<=', 0);
                });
            }
        }

        $employees = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        
        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Owner/Finance/Payrolls/Master', [
            'employees' => $employees,
            'bpjsTk' => $bpjsTk,
            'bpjsKes' => $bpjsKes,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => $request->only(['search', 'branch_id', 'division_id', 'salary_status']),
        ]);
    }

    public function index(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Payroll::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->input('division_id'));
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payrolls = $query->paginate(10)->withQueryString();
        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        $stats = [
            'total_disbursed' => Payroll::where('company_id', $company->id)->where('status', 'paid')->sum('total_amount'),
            'total_in_process' => Payroll::where('company_id', $company->id)->where('status', 'process')->sum('total_amount'),
            'total_payrolls_count' => Payroll::where('company_id', $company->id)->count(),
        ];

        $activeEmployees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'nip', 'basic_salary', 'branch_id', 'division_id', 'payroll_cycle')
            ->get();

        $missingSalary = $activeEmployees->filter(fn($e) => empty($e->basic_salary) || $e->basic_salary <= 0);

        $readiness = [
            'total_active' => $activeEmployees->count(),
            'missing_salary_employees' => $missingSalary->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'nip' => $e->nip,
                'branch_id' => $e->branch_id,
                'division_id' => $e->division_id,
                'payroll_cycle' => $e->payroll_cycle,
            ])->values(),
        ];

        return Inertia::render('Owner/Finance/Payrolls/Index', [
            'payrolls' => $payrolls,
            'branches' => $branches,
            'divisions' => $divisions,
            'stats' => $stats,
            'readiness' => $readiness,
            'filters' => $request->only(['search', 'start_date', 'end_date', 'branch_id', 'division_id', 'status']),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Payroll::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->input('division_id'));
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payrolls = $query->get();

        $filename = 'Rekap-Penggajian-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new PayrollSummaryExport($payrolls), $filename);
    }

    public function checkEmployees()
    {
        $company = auth()->user()->company;
        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $incomplete = [];
        $completeCount = 0;
        foreach ($employees as $emp) {
            if (empty($emp->basic_salary) || $emp->basic_salary == 0) {
                $incomplete[] = $emp->name;
            } else {
                $completeCount++;
            }
        }

        if (count($incomplete) > 0) {
            $names = implode(', ', array_slice($incomplete, 0, 5));
            if (count($incomplete) > 5) {
                $names .= ' dan ' . (count($incomplete) - 5) . ' lainnya';
            }
            return redirect()->back()->with('warning', count($incomplete) . ' karyawan belum memiliki Gaji Pokok (' . $names . '). ' . $completeCount . ' karyawan lainnya sudah siap diproses.');
        }

        return redirect()->back()->with('success', 'Semua data karyawan aktif (' . $employees->count() . ' orang) lengkap, siap generate penggajian.');
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payroll_cycle' => 'nullable|in:all,monthly,weekly',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
        ]);

        $duplicateQuery = Payroll::where('company_id', $company->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_date', $request->start_date)
            ->where('end_date', $request->end_date);

        if ($request->filled('branch_id')) {
            $duplicateQuery->where(function ($q) use ($request) {
                $q->whereNull('branch_id')->orWhere('branch_id', $request->branch_id);
            });
        }
        if ($request->filled('division_id')) {
            $duplicateQuery->where(function ($q) use ($request) {
                $q->whereNull('division_id')->orWhere('division_id', $request->division_id);
            });
        }

        $existingPayroll = $duplicateQuery->first();
        if ($existingPayroll) {
            $statusText = strtoupper($existingPayroll->status);
            $branchText = $existingPayroll->branch ? $existingPayroll->branch->name : 'Semua Cabang';
            $divText = $existingPayroll->division ? $existingPayroll->division->name : 'Semua Divisi';
            return redirect()->back()->withInput()->with('error', "Penggajian untuk periode {$request->start_date} s/d {$request->end_date} ({$branchText} - {$divText}) sudah ada (Kode: {$existingPayroll->code}, Status: {$statusText}).");
        }

        $query = Employee::where('company_id', $company->id)->where('is_active', true);
        
        if ($request->filled('payroll_cycle') && $request->payroll_cycle !== 'all') {
            $query->where('payroll_cycle', $request->payroll_cycle);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }
        
        $allEmployees = $query->get();
        if ($allEmployees->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada karyawan aktif untuk diproses.');
        }

        $validEmployees = $allEmployees->filter(function($emp) {
            return !empty($emp->basic_salary) && $emp->basic_salary > 0;
        });

        $skippedEmployees = $allEmployees->filter(function($emp) {
            return empty($emp->basic_salary) || $emp->basic_salary == 0;
        });

        if ($validEmployees->isEmpty()) {
            return redirect()->back()->with('error', 'Generate gagal: Semua karyawan yang dipilih belum memiliki data Gaji Pokok.');
        }

        $employees = $validEmployees;

        $createdPayrollId = null;

        DB::transaction(function () use ($request, $company, $employees, &$createdPayrollId) {
            $payroll = Payroll::create([
                'company_id' => $company->id,
                'code' => 'PY-' . date('Ym') . '-' . strtoupper(Str::random(4)),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'status' => 'process',
                'total_employees' => $employees->count(),
                'total_amount' => 0,
            ]);

            $createdPayrollId = $payroll->id;
            $totalAmount = 0;
            $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
            $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

            foreach ($employees as $emp) {
                $attendances = Attendance::where('employee_id', $emp->id)
                    ->whereBetween('date', [$request->start_date, $request->end_date])
                    ->get();

                $attendedDates = $attendances->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

                $approvedLeaves = LeaveRequest::where('employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->where(function($q) use ($request) {
                        $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function($sub) use ($request) {
                              $sub->where('start_date', '<=', $request->start_date)
                                  ->where('end_date', '>=', $request->end_date);
                          });
                    })
                    ->get();

                $leaveDates = [];
                foreach ($approvedLeaves as $leave) {
                    $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
                    foreach ($period as $dt) {
                        $leaveDates[] = $dt->format('Y-m-d');
                    }
                }
                $leaveDates = array_unique($leaveDates);

                $scheduledWorkSchedules = WorkSchedule::where('employee_id', $emp->id)
                    ->where('is_day_off', false)
                    ->whereBetween('date', [$request->start_date, $request->end_date])
                    ->get();

                $autoAlphaDays = 0;
                if ($scheduledWorkSchedules->count() > 0) {
                    foreach ($scheduledWorkSchedules as $ws) {
                        $schedDate = Carbon::parse($ws->date)->format('Y-m-d');
                        if (!in_array($schedDate, $attendedDates) && !in_array($schedDate, $leaveDates)) {
                            $autoAlphaDays++;
                        }
                    }
                }

                $explicitAlphaDays = $attendances->where('attendance_status', 'alpha')->count();
                $alphaDays = max($autoAlphaDays, $explicitAlphaDays);
                
                $totalLateMinutes = $attendances->sum('late_minutes');
                
                $latePenaltyAmount = 0;
                if ($totalLateMinutes > 0 && $emp->late_penalty_nominal > 0) {
                    if ($emp->late_penalty_type === 'flat') {
                        $lateDays = $attendances->where('late_minutes', '>', 0)->count();
                        $latePenaltyAmount = $lateDays * $emp->late_penalty_nominal;
                    } else {
                        $latePenaltyAmount = round($totalLateMinutes * ($emp->late_penalty_nominal / 60));
                    }
                }

                $alphaPenaltyAmount = 0;
                if ($alphaDays > 0) {
                    if ($emp->alpha_penalty_type === 'flat') {
                        $alphaPenaltyAmount = $alphaDays * $emp->alpha_penalty_nominal;
                    } else {
                        $dailyRate = $emp->basic_salary > 0 ? ($emp->basic_salary / 30) : 0;
                        $alphaPenaltyAmount = round($alphaDays * $dailyRate);
                    }
                }

                $overtimes = Overtime::where('employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->whereBetween('date', [$request->start_date, $request->end_date])
                    ->get();
                
                $totalOvertimeMinutes = $overtimes->sum('duration_minutes');
                $overtimeAmount = ($totalOvertimeMinutes / 60) * ($emp->overtime_rate_per_hour ?? 0);

                $bonus = 0;
                $otherDeductions = 0;
                $remarks = null;
                $loanInstallmentId = null;

                if ($emp->joined_at) {
                    $join = Carbon::parse($emp->joined_at);
                    $periodStart = Carbon::parse($request->start_date);
                    $periodEnd = Carbon::parse($request->end_date);
                    $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

                    if ($join->gt($periodStart) && $join->lte($periodEnd)) {
                        $activeDays = $join->diffInDays($periodEnd) + 1;
                        $proratedSalary = ($emp->basic_salary / $totalDaysInPeriod) * $activeDays;
                        $prorateDeduction = max(0, round($emp->basic_salary - $proratedSalary));
                        $otherDeductions += $prorateDeduction;
                        $remarks = 'Prorata gaji baru masuk tgl ' . $join->format('d/m/Y') . " ($activeDays/$totalDaysInPeriod hr)";
                    }
                }

                $unpaidLoanInstallment = LoanInstallment::whereHas('loan', function($q) use ($emp, $company) {
                        $q->where('employee_id', $emp->id)
                          ->where('company_id', $company->id)
                          ->where('status', 'approved');
                    })
                    ->where('status', 'unpaid')
                    ->where('due_date', '<=', $request->end_date)
                    ->whereDoesntHave('payrollItem', function($q) {
                        $q->whereHas('payroll', function($pq) {
                            $pq->where('status', 'process');
                        });
                    })
                    ->orderBy('due_date')
                    ->first();

                if ($unpaidLoanInstallment) {
                    $loanInstallmentId = $unpaidLoanInstallment->id;
                    $otherDeductions += $unpaidLoanInstallment->amount;
                    $remarks = $remarks ? ($remarks . ' & Kasbon') : 'Potongan Kasbon / Cicilan Pinjaman';
                }

                $approvedReimbursements = Reimbursement::where('employee_id', $emp->id)
                    ->where('company_id', $company->id)
                    ->where('status', 'approved')
                    ->where('payout_method', 'payroll')
                    ->whereNull('payroll_item_id')
                    ->where('date', '<=', $request->end_date)
                    ->get();

                $reimbursementAmount = (float) $approvedReimbursements->sum('amount');
                if ($reimbursementAmount > 0) {
                    $remarks = $remarks ? ($remarks . ' & Reimbursement') : 'Klaim Biaya / Reimbursement';
                }
                
                $bpjsKesEmp = 0;
                $bpjsKesComp = 0;
                
                if (($bpjsKes->is_active ?? true) && !empty($emp->bpjs_kesehatan_no)) {
                    $basisBpjsKes = $emp->basic_salary + ($emp->fixed_allowance ?? 0);
                    if ($bpjsKes->minimum_wage && $basisBpjsKes < $bpjsKes->minimum_wage) {
                        $basisBpjsKes = $bpjsKes->minimum_wage;
                    }
                    if ($bpjsKes->maximum_wage && $basisBpjsKes > $bpjsKes->maximum_wage) {
                        $basisBpjsKes = $bpjsKes->maximum_wage;
                    }

                    $bpjsKesEmp = ($basisBpjsKes * ($bpjsKes->employee_percent ?? 1.00)) / 100;
                    $bpjsKesComp = ($basisBpjsKes * ($bpjsKes->company_percent ?? 4.00)) / 100;
                }

                $basisBpjsTk = $emp->basic_salary + ($emp->fixed_allowance ?? 0);
                $basisJp = min($emp->basic_salary + ($emp->fixed_allowance ?? 0), (float)($bpjsTk->jp_maximum_wage ?? 10042300));

                $jhtEmp = 0;
                $jhtComp = 0;
                $jpEmp = 0;
                $jpComp = 0;
                $jkkComp = 0;
                $jkmComp = 0;

                if (!empty($emp->jht_no) || !empty($emp->jp_no) || !empty($emp->jkk_no) || !empty($emp->jkm_no)) {
                    $jhtEmp = ($bpjsTk->jht_active ?? true) ? (($basisBpjsTk * ($bpjsTk->jht_employee_percent ?? 2.00)) / 100) : 0;
                    $jhtComp = ($bpjsTk->jht_active ?? true) ? (($basisBpjsTk * ($bpjsTk->jht_company_percent ?? 3.70)) / 100) : 0;
                    
                    $jpEmp = ($bpjsTk->jp_active ?? true) ? (($basisJp * ($bpjsTk->jp_employee_percent ?? 1.00)) / 100) : 0;
                    $jpComp = ($bpjsTk->jp_active ?? true) ? (($basisJp * ($bpjsTk->jp_company_percent ?? 2.00)) / 100) : 0;
                    
                    $jkkComp = ($bpjsTk->jkk_active ?? true) ? (($basisBpjsTk * ($bpjsTk->jkk_percent ?? 0.24)) / 100) : 0;
                    $jkmComp = ($bpjsTk->jkm_active ?? true) ? (($basisBpjsTk * ($bpjsTk->jkm_percent ?? 0.30)) / 100) : 0;
                }

                $pph21Amount = 0;
                if ($emp->taxable) {
                    $ptkpYearly = 54000000;
                    if ($emp->ptkp_status === 'TK/1' || $emp->ptkp_status === 'K/0') $ptkpYearly = 58500000;
                    elseif ($emp->ptkp_status === 'TK/2' || $emp->ptkp_status === 'K/1') $ptkpYearly = 63000000;
                    elseif ($emp->ptkp_status === 'TK/3' || $emp->ptkp_status === 'K/2') $ptkpYearly = 67500000;
                    elseif ($emp->ptkp_status === 'K/3') $ptkpYearly = 72000000;
                    elseif ($emp->ptkp_status === 'K/I/0') $ptkpYearly = 112500000;
                    elseif ($emp->ptkp_status === 'K/I/1') $ptkpYearly = 117000000;
                    elseif ($emp->ptkp_status === 'K/I/2') $ptkpYearly = 121500000;
                    elseif ($emp->ptkp_status === 'K/I/3') $ptkpYearly = 126000000;

                    $grossPajak = min($emp->basic_salary, 12000000);
                    $biayaJabatan = min($grossPajak * 0.05, 500000);
                    $totalBpjsKaryawan = $bpjsKesEmp + $jhtEmp + $jpEmp;
                    $totalBpjsPerusahaanDeduction = $bpjsKesComp + $jkkComp + $jkmComp; 
                    
                    $netoMonthly = $grossPajak - $biayaJabatan - $totalBpjsKaryawan - $totalBpjsPerusahaanDeduction;
                    $netoYearly = $netoMonthly * 12;
                    $pkpYearly = max(0, $netoYearly - $ptkpYearly);

                    if ($pkpYearly > 0) {
                        $pph21Yearly = $pkpYearly * 0.05;
                        $pph21Amount = round($pph21Yearly / 12);
                    }
                }

                $presentDays = $attendances->whereNotIn('attendance_status', ['alpha', 'leave'])->count();
                $totalDailyAllowance = ($emp->daily_allowance ?? 0) * $presentDays;

                $totalEarnings = $emp->basic_salary + ($emp->fixed_allowance ?? 0) + $totalDailyAllowance + ($emp->other_allowance ?? 0) + $overtimeAmount + $bonus + $reimbursementAmount;
                
                $totalPenalty = $latePenaltyAmount + $alphaPenaltyAmount;
                $totalDeductions = $totalPenalty + $otherDeductions + $pph21Amount + $bpjsKesEmp + $jhtEmp + $jpEmp;
                $totalBenefits = $bpjsKesComp + $jhtComp + $jpComp + $jkkComp + $jkmComp;
                
                $netSalary = max(0, $totalEarnings - $totalDeductions);

                $payrollItem = $payroll->items()->create([
                    'employee_id' => $emp->id,
                    'basic_salary' => $emp->basic_salary,
                    'fixed_allowance' => $emp->fixed_allowance ?? 0,
                    'daily_allowance' => $totalDailyAllowance,
                    'other_allowance' => $emp->other_allowance ?? 0,
                    'overtime_amount' => $overtimeAmount,
                    'bonus' => $bonus,
                    'reimbursement_amount' => $reimbursementAmount,
                    'late_penalty_amount' => $latePenaltyAmount,
                    'alpha_penalty_amount' => $alphaPenaltyAmount,
                    'total_penalty' => $totalPenalty,
                    'other_deductions' => $otherDeductions,
                    'pph21_amount' => $pph21Amount,
                    'ptkp_status' => $emp->ptkp_status,
                    'bpjs_kesehatan_employee' => $bpjsKesEmp,
                    'bpjs_kesehatan_company' => $bpjsKesComp,
                    'jht_employee' => $jhtEmp,
                    'jht_company' => $jhtComp,
                    'jp_employee' => $jpEmp,
                    'jp_company' => $jpComp,
                    'jkk_company' => $jkkComp,
                    'jkm_company' => $jkmComp,
                    'total_earnings' => $totalEarnings,
                    'total_benefits' => $totalBenefits,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'loan_installment_id' => $loanInstallmentId,
                    'remarks' => $remarks
                ]);

                if ($approvedReimbursements->isNotEmpty()) {
                    foreach ($approvedReimbursements as $reimb) {
                        $reimb->update(['payroll_item_id' => $payrollItem->id]);
                    }
                }

                $totalAmount += $netSalary;
            }

            $payroll->update(['total_amount' => $totalAmount]);
        });

        if ($skippedEmployees->isNotEmpty()) {
            $skippedNames = $skippedEmployees->pluck('name')->take(3)->implode(', ');
            if ($skippedEmployees->count() > 3) {
                $skippedNames .= ' dan ' . ($skippedEmployees->count() - 3) . ' lainnya';
            }
            return redirect()->route('owner.finance.payrolls.show', $createdPayrollId)->with('success', "Penggajian berhasil digenerate untuk {$validEmployees->count()} karyawan. (Catatan: {$skippedEmployees->count()} dilewati karena Gaji Pokok belum diatur: {$skippedNames}).");
        }

        return redirect()->route('owner.finance.payrolls.show', $createdPayrollId)->with('success', 'Penggajian berhasil digenerate.');
    }

    public function show($id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::with([
            'branch:id,name',
            'division:id,name',
            'items.employee:id,name,nip,branch_id,division_id,position_id,joined_at',
            'items.employee.division:id,name',
            'items.employee.position:id,name',
            'items.employee.branch:id,name',
            'items.loanInstallment.loan',
            'items.reimbursements'
        ])->where('company_id', $company->id)->findOrFail($id);

        $unpaidLoanInstallments = LoanInstallment::whereHas('loan', function($q) use ($company) {
                $q->where('company_id', $company->id)->where('status', 'approved');
            })
            ->where('status', 'unpaid')
            ->where('due_date', '<=', $payroll->end_date)
            ->whereDoesntHave('payrollItem', function($q) use ($payroll) {
                $q->where('payroll_id', '!=', $payroll->id)
                  ->whereHas('payroll', function($pq) {
                      $pq->where('status', 'process');
                  });
            })
            ->with('loan')
            ->get()
            ->groupBy(function($item) {
                return $item->loan->employee_id;
            });

        return Inertia::render('Owner/Finance/Payrolls/Show', [
            'payroll' => $payroll,
            'unpaidLoanInstallments' => $unpaidLoanInstallments,
        ]);
    }

    public function showItem($id, $itemId)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::where('company_id', $company->id)->findOrFail($id);

        $item = $payroll->items()
            ->with([
                'employee:id,name,nip,basic_salary,branch_id,division_id,position_id,joined_at,bank_name,bank_account_number,bank_account_name,bpjs_kesehatan_no,jht_no,jp_no,ptkp_status',
                'employee.division:id,name',
                'employee.position:id,name',
                'employee.branch:id,name',
                'employee.company:id,name_company',
                'loanInstallment.loan',
                'reimbursements'
            ])
            ->findOrFail($itemId);

        $startDate = Carbon::parse($payroll->start_date);
        $endDate = Carbon::parse($payroll->end_date);
        $totalDaysInPeriod = $startDate->diffInDays($endDate) + 1;

        $attendances = Attendance::where('employee_id', $item->employee_id)
            ->whereBetween('date', [$payroll->start_date, $payroll->end_date])
            ->get();

        $presentCount = $attendances->whereNotIn('attendance_status', ['alpha', 'absent', 'leave'])->count();
        $lateCount = $attendances->where('late_minutes', '>', 0)->count();

        $attendedDates = $attendances->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

        $approvedLeaves = LeaveRequest::where('employee_id', $item->employee_id)
            ->where('status', 'approved')
            ->where(function($q) use ($payroll) {
                $q->whereBetween('start_date', [$payroll->start_date, $payroll->end_date])
                  ->orWhereBetween('end_date', [$payroll->start_date, $payroll->end_date])
                  ->orWhere(function($sub) use ($payroll) {
                      $sub->where('start_date', '<=', $payroll->start_date)
                          ->where('end_date', '>=', $payroll->end_date);
                  });
            })
            ->get();

        $leaveDates = [];
        foreach ($approvedLeaves as $leave) {
            $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($period as $dt) {
                $leaveDates[] = $dt->format('Y-m-d');
            }
        }
        $leaveDates = array_unique($leaveDates);
        $leaveCount = count($leaveDates);

        $scheduledWorkSchedules = WorkSchedule::where('employee_id', $item->employee_id)
            ->where('is_day_off', false)
            ->whereBetween('date', [$payroll->start_date, $payroll->end_date])
            ->get();

        $autoAlphaDays = 0;
        if ($scheduledWorkSchedules->count() > 0) {
            foreach ($scheduledWorkSchedules as $ws) {
                $schedDate = Carbon::parse($ws->date)->format('Y-m-d');
                if (!in_array($schedDate, $attendedDates) && !in_array($schedDate, $leaveDates)) {
                    $autoAlphaDays++;
                }
            }
        }

        $explicitAlphaDays = $attendances->filter(fn($a) => in_array($a->attendance_status, ['alpha', 'absent']))->count();
        $absentCount = max($autoAlphaDays, $explicitAlphaDays);

        $attendancePercent = $totalDaysInPeriod > 0 ? round(($presentCount / $totalDaysInPeriod) * 100) : 0;

        $attendanceSummary = [
            'total_days' => $totalDaysInPeriod,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'leave' => $leaveCount,
            'percent' => $attendancePercent,
        ];

        // Prorata calculation details
        $prorataDetails = null;
        if ($item->employee?->joined_at) {
            $join = Carbon::parse($item->employee->joined_at);
            if ($join->gt($startDate) && $join->lte($endDate)) {
                $activeDays = $join->diffInDays($endDate) + 1;
                $proratedSalary = ($item->basic_salary / $totalDaysInPeriod) * $activeDays;
                $prorateAmount = max(0, round($item->basic_salary - $proratedSalary));
                $prorataDetails = [
                    'join_date' => $join->format('Y-m-d'),
                    'join_date_formatted' => $join->locale('id')->isoFormat('D MMMM Y'),
                    'active_days' => $activeDays,
                    'total_days' => $totalDaysInPeriod,
                    'prorate_amount' => $prorateAmount,
                ];
            }
        }

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        return Inertia::render('Owner/Finance/Payrolls/ItemShow', [
            'payroll' => $payroll,
            'item' => $item,
            'company' => $company,
            'attendanceSummary' => $attendanceSummary,
            'prorataDetails' => $prorataDetails,
            'bpjsTk' => $bpjsTk,
            'bpjsKes' => $bpjsKes,
        ]);
    }

    public function printSlips($id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::with([
            'items.employee.position',
            'items.employee.division',
            'items.employee.company',
            'items.reimbursements'
        ])
        ->where('company_id', $company->id)
        ->findOrFail($id);

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $pdf = Pdf::loadView('pdf.payroll-slip', compact('payroll', 'company', 'bpjsTk', 'bpjsKes'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Slip-Gaji-'. $payroll->code .'.pdf');
    }

    public function printSingleSlip($id, $itemId)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::with([
            'items' => function($q) use ($itemId) {
                $q->where('id', $itemId)->with([
                    'employee.position',
                    'employee.division',
                    'employee.company',
                    'reimbursements'
                ]);
            }
        ])
        ->where('company_id', $company->id)
        ->findOrFail($id);

        if ($payroll->items->isEmpty()) {
            abort(404, 'Data slip gaji tidak ditemukan');
        }

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $pdf = Pdf::loadView('pdf.payroll-slip', compact('payroll', 'company', 'bpjsTk', 'bpjsKes'));
        $pdf->setPaper('a4', 'portrait');

        $employeeName = Str::slug($payroll->items->first()->employee?->name ?? 'karyawan');
        return $pdf->stream('Slip-Gaji-'. $employeeName .'-'. $payroll->code .'.pdf');
    }

    public function exportBankExcel($id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::with(['items.employee'])->where('company_id', $company->id)->findOrFail($id);

        $period = Carbon::parse($payroll->start_date)->locale('id')->translatedFormat('F-Y');
        $filename = 'Bank-Transfer-' . Str::slug($payroll->code) . '-' . $period . '.xlsx';

        return Excel::download(new BankTransferExport($payroll), $filename);
    }

    public function exportBankCsv($id)
    {
        return $this->exportBankExcel($id);
    }

    public function updateStatus(Request $request, $id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::where('company_id', $company->id)->findOrFail($id);
        
        $request->validate(['status' => 'required|in:process,paid,cancelled']);

        $updateData = ['status' => $request->status];
        if ($request->status === 'paid') {
            $updateData['paid_at'] = now();

            foreach ($payroll->items()->whereNotNull('loan_installment_id')->get() as $item) {
                $installment = LoanInstallment::find($item->loan_installment_id);
                if ($installment) {
                    $installment->update(['status' => 'paid']);
                    $loan = $installment->loan;
                    if ($loan && $loan->installments()->where('status', 'unpaid')->count() === 0) {
                        $loan->update(['status' => 'paid']);
                    }
                }
            }

            foreach ($payroll->items as $item) {
                Reimbursement::where('payroll_item_id', $item->id)->update(['status' => 'paid']);
            }
        } elseif ($request->status === 'cancelled') {
            $updateData['paid_at'] = null;

            foreach ($payroll->items()->whereNotNull('loan_installment_id')->get() as $item) {
                $installment = LoanInstallment::find($item->loan_installment_id);
                if ($installment) {
                    $installment->update(['status' => 'unpaid']);
                    $loan = $installment->loan;
                    if ($loan) {
                        $loan->update(['status' => 'approved']);
                    }
                }
                $item->update(['loan_installment_id' => null]);
            }

            foreach ($payroll->items as $item) {
                Reimbursement::where('payroll_item_id', $item->id)->update([
                    'payroll_item_id' => null,
                    'status' => 'approved',
                ]);
            }
        } elseif ($request->status !== 'paid') {
            $updateData['paid_at'] = null;

            foreach ($payroll->items()->whereNotNull('loan_installment_id')->get() as $item) {
                $installment = LoanInstallment::find($item->loan_installment_id);
                if ($installment) {
                    $installment->update(['status' => 'unpaid']);
                    $loan = $installment->loan;
                    if ($loan) {
                        $loan->update(['status' => 'approved']);
                    }
                }
            }

            foreach ($payroll->items as $item) {
                Reimbursement::where('payroll_item_id', $item->id)->update(['status' => 'approved']);
            }
        }

        $payroll->update($updateData);

        return redirect()->back()->with('success', 'Status penggajian berhasil diperbarui.');
    }

    public function updateItems(Request $request, $id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::where('company_id', $company->id)->findOrFail($id);

        if ($payroll->status === 'paid') {
            return redirect()->back()->with('error', 'Tidak dapat mengubah data penyesuaian karena status penggajian sudah PAID.');
        }

        $itemsData = $request->input('items', []);

        DB::transaction(function () use ($payroll, $itemsData) {
            foreach ($itemsData as $itemId => $data) {
                $item = $payroll->items()->find($itemId);
                if (!$item) continue;

                $bonus = (float) ($data['bonus'] ?? 0);
                $otherDeductions = (float) ($data['other_deductions'] ?? 0);
                $remarks = array_key_exists('remarks', $data) ? $data['remarks'] : $item->remarks;
                $loanInstallmentId = !empty($data['loan_installment_id']) ? $data['loan_installment_id'] : null;

                $reimbursementAmount = (float) ($item->reimbursement_amount ?? 0);
                $totalEarnings = $item->basic_salary + $item->fixed_allowance + $item->daily_allowance + $item->other_allowance + $item->overtime_amount + $reimbursementAmount + $bonus;
                $totalDeductions = $item->total_penalty + $otherDeductions + $item->pph21_amount + $item->bpjs_kesehatan_employee + $item->jht_employee + $item->jp_employee;
                $netSalary = max(0, $totalEarnings - $totalDeductions);

                $item->update([
                    'bonus' => $bonus,
                    'other_deductions' => $otherDeductions,
                    'total_earnings' => $totalEarnings,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'remarks' => $remarks,
                    'loan_installment_id' => $loanInstallmentId,
                ]);
            }

            $payroll->update([
                'total_amount' => $payroll->items()->sum('net_salary'),
            ]);
        });

        return redirect()->back()->with('success', 'Penyesuaian penggajian berhasil disimpan.');
    }

    public function destroy($id)
    {
        $company = auth()->user()->company;
        $payroll = Payroll::where('company_id', $company->id)->findOrFail($id);

        if ($payroll->status === 'paid') {
            return redirect()->back()->with('error', 'Data penggajian yang sudah berstatus PAID tidak dapat dihapus demi integritas pembukuan keuangan.');
        }

        foreach ($payroll->items()->whereNotNull('loan_installment_id')->get() as $item) {
            $installment = LoanInstallment::find($item->loan_installment_id);
            if ($installment) {
                $installment->update(['status' => 'unpaid']);
                $loan = $installment->loan;
                if ($loan) {
                    $loan->update(['status' => 'approved']);
                }
            }
        }

        foreach ($payroll->items as $item) {
            Reimbursement::where('payroll_item_id', $item->id)->update([
                'payroll_item_id' => null,
                'status' => 'approved',
            ]);
        }

        $payroll->delete();

        return redirect()->route('owner.finance.payrolls.index')->with('success', 'Data penggajian berhasil dihapus.');
    }
}
