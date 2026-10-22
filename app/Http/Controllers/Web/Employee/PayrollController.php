<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\PayrollItem;
use App\Models\ThrItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403, 'Data karyawan tidak ditemukan.');

        $selectedYear = (int) $request->input('year', date('Y'));

        $query = PayrollItem::with([
            'payroll',
            'loanInstallment.loan',
            'employee.position',
            'employee.division',
            'employee.branch',
        ])
        ->where('employee_id', $employee->id)
        ->whereHas('payroll', function ($q) use ($selectedYear) {
            $q->where('status', 'paid');
            if ($selectedYear) {
                $q->whereYear('start_date', $selectedYear);
            }
        });

        $payrollItems = $query->latest('id')->paginate(10)->withQueryString();

        $payrollItems->getCollection()->transform(function ($item) use ($employee) {
            $payroll = $item->payroll;
            $start = $payroll && $payroll->start_date ? Carbon::parse($payroll->start_date) : null;
            $end = $payroll && $payroll->end_date ? Carbon::parse($payroll->end_date) : null;

            return [
                'id' => $item->id,
                'payroll_id' => $item->payroll_id,
                'payroll_code' => $payroll ? $payroll->code : 'PAY-' . $item->id,
                'period_label' => $start ? $start->locale('id')->translatedFormat('F Y') : '-',
                'period_start' => $start ? $start->format('d M Y') : '-',
                'period_end' => $end ? $end->format('d M Y') : '-',
                'paid_at' => ($payroll && $payroll->paid_at) ? Carbon::parse($payroll->paid_at)->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : 'Sudah Dibayar',
                'net_salary' => (int) $item->net_salary,
                'total_earnings' => (int) $item->total_earnings,
                'total_deductions' => (int) $item->total_deductions,
                'basic_salary' => (int) $item->basic_salary,
                'fixed_allowance' => (int) $item->fixed_allowance,
                'daily_allowance' => (int) $item->daily_allowance,
                'overtime_amount' => (int) $item->overtime_amount,
                'bonus' => (int) $item->bonus,
                'reimbursement_amount' => (int) $item->reimbursement_amount,
                'other_allowance' => (int) ($item->other_allowance ?? 0),
                'late_penalty_amount' => (int) $item->late_penalty_amount,
                'alpha_penalty_amount' => (int) $item->alpha_penalty_amount,
                'pph21_amount' => (int) $item->pph21_amount,
                'bpjs_kesehatan_employee' => (int) $item->bpjs_kesehatan_employee,
                'jht_employee' => (int) $item->jht_employee,
                'jp_employee' => (int) $item->jp_employee,
                'loan_deduction' => (int) ($item->loanInstallment ? $item->loanInstallment->amount : 0),
                'other_deductions' => (int) $item->other_deductions,
                'bpjs_kesehatan_company' => (int) ($item->bpjs_kesehatan_company ?? 0),
                'jht_company' => (int) ($item->jht_company ?? 0),
                'jp_company' => (int) ($item->jp_company ?? 0),
                'jkk_company' => (int) ($item->jkk_company ?? 0),
                'jkm_company' => (int) ($item->jkm_company ?? 0),
                'remarks' => $item->remarks,
                'download_url' => route('employee.payrolls.download', $item->id),
            ];
        });

        $years = range(date('Y') - 3, date('Y') + 1);

        return Inertia::render('Employee/Payrolls/Index', [
            'payrolls' => $payrollItems,
            'year' => $selectedYear,
            'years' => $years,
            'filters' => [
                'year' => $selectedYear,
            ],
        ]);
    }

    public function downloadPdf($id, Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $company = $employee->company;
        if (!$company) abort(403);

        $item = PayrollItem::with([
            'payroll',
            'loanInstallment.loan',
            'employee.position',
            'employee.division',
            'employee.branch',
        ])
        ->where('id', $id)
        ->where('employee_id', $employee->id)
        ->whereHas('payroll', function ($q) {
            $q->where('status', 'paid');
        })
        ->firstOrFail();

        $payroll = $item->payroll;
        $payroll->setRelation('items', collect([$item]));

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $pdf = Pdf::loadView('pdf.payroll-slip', compact('payroll', 'company', 'bpjsTk', 'bpjsKes'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Slip-Gaji-' . Str::slug($employee->name) . '-' . $payroll->code . '.pdf';
        return $pdf->stream($fileName);
    }
}
