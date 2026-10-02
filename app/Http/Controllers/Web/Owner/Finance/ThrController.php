<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use App\Models\Thr;
use App\Models\ThrItem;
use App\Models\Employee;
use App\Models\Branch;
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

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();

        $previewData = null;
        if ($request->filled('payment_date') && $request->filled('year')) {
            $paymentDate = Carbon::parse($request->payment_date);

            $query = Employee::where('company_id', $company->id)
                ->where('is_active', true)
                ->whereNotNull('joined_at')
                ->with(['division:id,name', 'branch:id,name']);

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            $employees = $query->get();

            $calculated = collect();
            foreach ($employees as $employee) {
                $joinedAt = Carbon::parse($employee->joined_at);
                $diffInDays = $joinedAt->diffInDays($paymentDate, false);
                
                if ($diffInDays < 0) {
                    continue;
                }

                $monthsTenure = (int) floor($joinedAt->diffInDays($paymentDate) / 30.436875);

                if ($monthsTenure < $settings->min_months_tenure) {
                    continue;
                }

                $basis = (float) $employee->basic_salary;
                if ($settings->include_fixed_allowance) {
                    $basis += (float) $employee->fixed_allowance;
                }

                if ($monthsTenure < $settings->full_thr_months_tenure) {
                    $prorateMultiplier = round($monthsTenure / 12, 4);
                    $thrAmount = round(($basis * $monthsTenure) / 12);
                } else {
                    $prorateMultiplier = 1.0;
                    $thrAmount = $basis;
                }

                $calculated->push([
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'nip' => $employee->nip,
                    'division' => $employee->division ? $employee->division->name : '-',
                    'branch' => $employee->branch ? $employee->branch->name : '-',
                    'joined_at' => $joinedAt->format('Y-m-d'),
                    'months_tenure' => $monthsTenure,
                    'basis' => $basis,
                    'prorate_multiplier' => $prorateMultiplier,
                    'thr_amount' => $thrAmount,
                ]);
            }

            $previewData = $calculated;
        }

        return Inertia::render('Owner/Finance/Thr/Create', [
            'branches' => $branches,
            'settings' => $settings,
            'previewData' => $previewData,
            'params' => $request->only(['holiday_name', 'year', 'payment_date', 'branch_id']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_name' => 'required|string|max:255',
            'year' => 'required|integer',
            'payment_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
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

        $paymentDate = Carbon::parse($request->payment_date);

        $employees = Employee::where('company_id', $company->id)
            ->whereIn('id', $request->employee_ids)
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'Karyawan yang dipilih tidak valid.');
        }

        DB::beginTransaction();
        try {
            $thr = Thr::create([
                'company_id' => $company->id,
                'code' => 'THR-' . strtoupper(Str::random(6)) . '-' . date('Ym'),
                'year' => $request->year,
                'holiday_name' => $request->holiday_name,
                'payment_date' => $paymentDate,
                'branch_id' => $request->branch_id,
                'status' => 'process',
                'total_employees' => 0,
                'total_amount' => 0,
            ]);

            $totalEmployees = 0;
            $totalAmount = 0;

            foreach ($employees as $employee) {
                $joinedAt = Carbon::parse($employee->joined_at);
                $diffInDays = $joinedAt->diffInDays($paymentDate, false);
                
                if ($diffInDays < 0) {
                    continue;
                }

                $monthsTenure = (int) floor($joinedAt->diffInDays($paymentDate) / 30.436875);

                if ($monthsTenure < $settings->min_months_tenure) {
                    continue;
                }

                $basis = (float) $employee->basic_salary;
                if ($settings->include_fixed_allowance) {
                    $basis += (float) $employee->fixed_allowance;
                }

                if ($monthsTenure < $settings->full_thr_months_tenure) {
                    $prorateMultiplier = round($monthsTenure / 12, 4);
                    $thrAmount = round(($basis * $monthsTenure) / 12);
                } else {
                    $prorateMultiplier = 1.0;
                    $thrAmount = $basis;
                }
                
                $taxAmount = 0;
                $netAmount = $thrAmount - $taxAmount;

                ThrItem::create([
                    'thr_id' => $thr->id,
                    'employee_id' => $employee->id,
                    'tenure_months' => $monthsTenure,
                    'basis_amount' => $basis,
                    'prorate_multiplier' => $prorateMultiplier,
                    'thr_amount' => $thrAmount,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $netAmount,
                ]);

                $totalEmployees++;
                $totalAmount += $netAmount;
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
                'items.employee:id,name,nip,branch_id,division_id,position_id',
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
}
