<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use App\Models\Thr;
use App\Models\ThrItem;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\CompanyThrSetting;
use App\Models\CompanyPayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ThrController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $thrs = Thr::with('branch:id,name')
            ->where('company_id', $company->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('Owner/Finance/Thr/Index', [
            'thrs' => $thrs,
        ]);
    }

    public function create(Request $request)
    {
        $company = auth()->user()->company;
        $settings = $company->thrSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'is_active' => true,
                'min_months_tenure' => 1,
                'full_thr_months_tenure' => 12,
                'include_fixed_allowance' => false,
            ]
        );

        if (!$settings->is_active) {
            return redirect()->route('owner.finance.thr.settings.index')->with('error', 'Fitur THR dinonaktifkan di Pengaturan.');
        }

        $payrollSetting = $company->payrollSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'default_payroll_cycle' => 'monthly',
                'monthly_working_days' => 25,
                'weekly_working_days' => 6,
            ]
        );

        $monthlyWorkingDays = (int) ($payrollSetting->monthly_working_days ?: 25);
        $weeklyWorkingDays = (int) ($payrollSetting->weekly_working_days ?: 6);

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();

        $previewData = null;
        if ($request->filled('payment_date') && $request->filled('year')) {
            $paymentDate = $this->parseDateSafely($request->payment_date);

            $query = Employee::where('company_id', $company->id)
                ->where('is_active', true)
                ->whereNotNull('joined_at')
                ->with(['division:id,name', 'position:id,name', 'branch:id,name']);

            $branchId = $request->filled('branch_id') && is_numeric($request->branch_id)
                ? (int) $request->branch_id
                : null;

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $employees = $query->get();

            $calculated = collect();
            foreach ($employees as $employee) {
                $calc = $this->calculateEmployeeThr(
                    $employee,
                    $paymentDate,
                    $settings,
                    $monthlyWorkingDays,
                    $weeklyWorkingDays
                );

                if ($calc) {
                    $calculated->push($calc);
                }
            }

            $previewData = $calculated->values()->all();
        }

        return Inertia::render('Owner/Finance/Thr/Create', [
            'branches' => $branches,
            'settings' => $settings,
            'payrollSetting' => $payrollSetting,
            'previewData' => $previewData,
            'params' => [
                'holiday_name' => $request->holiday_name ?: 'Hari Raya Idul Fitri',
                'year' => $request->year ? (int) $request->year : (int) date('Y'),
                'payment_date' => $request->payment_date ?: date('Y-m-d'),
                'branch_id' => $request->filled('branch_id') && is_numeric($request->branch_id) ? (int) $request->branch_id : '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_name' => 'required|string|max:255',
            'year' => 'required|integer',
            'payment_date' => 'required',
            'branch_id' => 'nullable',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $company = auth()->user()->company;
        $settings = $company->thrSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'is_active' => true,
                'min_months_tenure' => 1,
                'full_thr_months_tenure' => 12,
                'include_fixed_allowance' => false,
            ]
        );

        if (!$settings->is_active) {
            return redirect()->route('owner.finance.thr.settings.index')->with('error', 'Fitur THR dinonaktifkan di Pengaturan.');
        }

        $payrollSetting = $company->payrollSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'default_payroll_cycle' => 'monthly',
                'monthly_working_days' => 25,
                'weekly_working_days' => 6,
            ]
        );

        $monthlyWorkingDays = (int) ($payrollSetting->monthly_working_days ?: 25);
        $weeklyWorkingDays = (int) ($payrollSetting->weekly_working_days ?: 6);

        $branchId = $request->filled('branch_id') && is_numeric($request->branch_id)
            ? (int) $request->branch_id
            : null;

        if ($branchId && !Branch::where('company_id', $company->id)->where('id', $branchId)->exists()) {
            return redirect()->back()->with('error', 'Cabang yang dipilih tidak valid.');
        }

        $paymentDate = $this->parseDateSafely($request->payment_date);

        $employees = Employee::where('company_id', $company->id)
            ->whereIn('id', $request->employee_ids)
            ->where('is_active', true)
            ->whereNotNull('joined_at')
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'Karyawan yang dipilih tidak valid.');
        }

        DB::beginTransaction();
        try {
            $thr = Thr::create([
                'company_id' => $company->id,
                'code' => 'THR-' . strtoupper(Str::random(6)) . '-' . date('Ym'),
                'year' => (int) $request->year,
                'holiday_name' => $request->holiday_name,
                'payment_date' => $paymentDate,
                'branch_id' => $branchId,
                'status' => 'process',
                'total_employees' => 0,
                'total_amount' => 0,
            ]);

            $totalEmployees = 0;
            $totalAmount = 0;

            foreach ($employees as $employee) {
                $calc = $this->calculateEmployeeThr(
                    $employee,
                    $paymentDate,
                    $settings,
                    $monthlyWorkingDays,
                    $weeklyWorkingDays
                );

                if (!$calc) {
                    continue;
                }

                ThrItem::create([
                    'thr_id' => $thr->id,
                    'employee_id' => $employee->id,
                    'tenure_months' => $calc['months_tenure'],
                    'basis_amount' => $calc['basis'],
                    'prorate_multiplier' => $calc['prorate_multiplier'],
                    'thr_amount' => $calc['thr_amount'],
                    'tax_amount' => $calc['tax_amount'],
                    'net_amount' => $calc['net_amount'],
                ]);

                $totalEmployees++;
                $totalAmount += $calc['net_amount'];
            }

            if ($totalEmployees === 0) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ada karyawan yang memenuhi kriteria perhitungan THR.');
            }

            $thr->update([
                'total_employees' => $totalEmployees,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return redirect()->route('owner.finance.thr.show', $thr->id)->with('success', 'Perhitungan THR berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $company = auth()->user()->company;
        $thr = Thr::where('company_id', $company->id)
            ->with([
                'branch:id,name',
                'items.employee:id,name,nip,branch_id,division_id,position_id,payroll_cycle,basic_salary,fixed_allowance',
                'items.employee.division:id,name',
                'items.employee.position:id,name',
                'items.employee.branch:id,name'
            ])
            ->findOrFail($id);
        
        return Inertia::render('Owner/Finance/Thr/Show', [
            'thr' => $thr,
        ]);
    }

    public function slip($id)
    {
        $company = auth()->user()->company;
        $thr = Thr::where('company_id', $company->id)
            ->with([
                'items.employee.division',
                'items.employee.position'
            ])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.thr-slip', compact('thr', 'company'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Slip-THR-'. $thr->code .'.pdf');
    }

    public function slipSingle($id, $itemId)
    {
        $company = auth()->user()->company;
        $thr = Thr::where('company_id', $company->id)->findOrFail($id);

        $item = ThrItem::where('thr_id', $thr->id)
            ->with([
                'employee.division',
                'employee.position'
            ])
            ->findOrFail($itemId);

        $thr->setRelation('items', collect([$item]));

        $pdf = Pdf::loadView('pdf.thr-slip', compact('thr', 'company'));
        $pdf->setPaper('a4', 'portrait');

        $employeeName = Str::slug($item->employee->name);
        return $pdf->stream('Slip-THR-'. $employeeName .'-'. $thr->code .'.pdf');
    }

    public function pay($id)
    {
        $company = auth()->user()->company;
        $thr = Thr::where('company_id', $company->id)->findOrFail($id);
        
        if ($thr->status !== 'process') {
            return redirect()->back()->with('error', 'THR ini sudah dibayar.');
        }

        $thr->update(['status' => 'paid']);

        return redirect()->back()->with('success', 'THR berhasil ditandai sebagai PAID.');
    }

    public function destroy($id)
    {
        $company = auth()->user()->company;
        $thr = Thr::where('company_id', $company->id)->findOrFail($id);
        
        if ($thr->status !== 'process') {
            return redirect()->back()->with('error', 'Hanya THR berstatus DRAFT/PROCESS yang dapat dihapus.');
        }

        $thr->delete();

        return redirect()->route('owner.finance.thr.index')->with('success', 'Data THR berhasil dihapus.');
    }

    /**
     * Calculate individual employee THR eligibility and amounts based on labor rules
     */
    private function calculateEmployeeThr(
        Employee $employee,
        Carbon $paymentDate,
        CompanyThrSetting $settings,
        int $monthlyWorkingDays,
        int $weeklyWorkingDays
    ): ?array {
        if (!$employee->joined_at) {
            return null;
        }

        $joinedAt = Carbon::parse($employee->joined_at);
        if ($joinedAt->gt($paymentDate)) {
            return null;
        }

        // Hitung masa kerja dalam bulan secara akurat
        $monthsTenure = (int) $joinedAt->diffInMonths($paymentDate);

        // Jika masa kerja kurang dari syarat minimal (default: 1 bulan) -> Tidak berhak THR
        if ($monthsTenure < $settings->min_months_tenure) {
            return null;
        }

        $cycle = $employee->payroll_cycle ?: 'monthly';
        $rawSalary = (float) $employee->basic_salary;

        // Upah 1 bulan ekuivalen untuk dasar perhitungan THR:
        // 1. Bulanan: Gaji pokok per bulan
        // 2. Harian: Upah harian * hari kerja bulanan (Permenaker No. 6/2016 Pasal 3 ayat 2)
        // 3. Mingguan: (Upah mingguan / hari kerja mingguan) * hari kerja bulanan
        if ($cycle === 'daily') {
            $monthlyEquivalentSalary = $rawSalary * $monthlyWorkingDays;
        } elseif ($cycle === 'weekly') {
            $dailyRate = $weeklyWorkingDays > 0 ? ($rawSalary / $weeklyWorkingDays) : ($rawSalary / 6);
            $monthlyEquivalentSalary = $dailyRate * $monthlyWorkingDays;
        } else {
            $monthlyEquivalentSalary = $rawSalary;
        }

        $basis = $monthlyEquivalentSalary;
        if ($settings->include_fixed_allowance) {
            $basis += (float) ($employee->fixed_allowance ?? 0);
        }

        // Perhitungan hak THR: Penuh (1x Gaji) atau Prorata (Masa Kerja / 12)
        if ($monthsTenure < $settings->full_thr_months_tenure) {
            $prorateMultiplier = round($monthsTenure / 12, 2);
            $thrAmount = round(($basis * $monthsTenure) / 12);
        } else {
            $prorateMultiplier = 1.0;
            $thrAmount = round($basis);
        }

        $taxAmount = 0; // PPh21 THR disiapkan 0 untuk MVP
        $netAmount = max(0, $thrAmount - $taxAmount);

        return [
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'nip' => $employee->nip,
            'division' => $employee->division ? $employee->division->name : '-',
            'position' => $employee->position ? $employee->position->name : '-',
            'branch' => $employee->branch ? $employee->branch->name : '-',
            'branch_id' => $employee->branch_id,
            'payroll_cycle' => $cycle,
            'raw_basic_salary' => $rawSalary,
            'fixed_allowance' => (float) ($employee->fixed_allowance ?? 0),
            'joined_at' => $joinedAt->format('Y-m-d'),
            'months_tenure' => $monthsTenure,
            'basis' => (int) round($basis),
            'prorate_multiplier' => (float) $prorateMultiplier,
            'thr_amount' => (int) round($thrAmount),
            'tax_amount' => (int) round($taxAmount),
            'net_amount' => (int) round($netAmount),
        ];
    }

    private function parseDateSafely($date): Carbon
    {
        if (!$date) {
            return Carbon::now();
        }

        $dateStr = trim((string) $date);

        try {
            if (str_contains($dateStr, '/')) {
                $parts = explode('/', $dateStr);
                if (count($parts) === 3 && strlen($parts[2]) === 4) {
                    // dd/mm/yyyy
                    return Carbon::createFromFormat('d/m/Y', $dateStr)->startOfDay();
                } elseif (count($parts) === 3 && strlen($parts[0]) === 4) {
                    // yyyy/mm/dd
                    return Carbon::createFromFormat('Y/m/d', $dateStr)->startOfDay();
                }
            }
            return Carbon::parse($dateStr)->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfDay();
        }
    }
}
