<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    /**
     * Get list of payrolls (payslips) for the authenticated employee,
     * including full earnings and deductions breakdown for direct detail views.
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

        $query = PayrollItem::with([
            'payroll',
            'loanInstallment.loan',
            'employee.position',
            'employee.division',
            'employee.branch',
        ])
        ->where('employee_id', $employee->id)
        ->whereHas('payroll', function ($q) {
            $q->whereIn('status', ['process', 'paid']);
        });

        if ($request->filled('year')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->whereYear('start_date', $request->year);
            });
        }

        if ($request->filled('month')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->whereMonth('start_date', $request->month);
            });
        }

        $payrollItems = $query->latest('id')->paginate($this->getPerPage($request));

        $payrollItems->getCollection()->transform(function ($item) {
            $payroll = $item->payroll;

            return [
                'id' => $item->id,
                'payroll_id' => $item->payroll_id,
                'payroll_code' => $payroll ? $payroll->code : null,
                'period_start' => $payroll ? Carbon::parse($payroll->start_date)->format('Y-m-d') : null,
                'period_end' => $payroll ? Carbon::parse($payroll->end_date)->format('Y-m-d') : null,
                'payment_status' => $payroll ? $payroll->status : 'process',
                'paid_at' => ($payroll && $payroll->paid_at) ? Carbon::parse($payroll->paid_at)->format('Y-m-d H:i') : null,

                // Summary Totals
                'net_salary' => (int) $item->net_salary,
                'total_earnings' => (int) $item->total_earnings,
                'total_deductions' => (int) $item->total_deductions,
                'total_benefits' => (int) $item->total_benefits,

                // Detailed Earnings Breakdown
                'earnings' => [
                    'basic_salary' => (int) $item->basic_salary,
                    'fixed_allowance' => (int) $item->fixed_allowance,
                    'daily_allowance' => (int) $item->daily_allowance,
                    'other_allowance' => (int) $item->other_allowance,
                    'overtime_amount' => (int) $item->overtime_amount,
                    'bonus' => (int) $item->bonus,
                    'reimbursement_amount' => (int) $item->reimbursement_amount,
                ],

                // Detailed Deductions Breakdown
                'deductions' => [
                    'late_penalty_amount' => (int) $item->late_penalty_amount,
                    'alpha_penalty_amount' => (int) $item->alpha_penalty_amount,
                    'total_penalty' => (int) $item->total_penalty,
                    'pph21_amount' => (int) $item->pph21_amount,
                    'bpjs_kesehatan_employee' => (int) $item->bpjs_kesehatan_employee,
                    'jht_employee' => (int) $item->jht_employee,
                    'jp_employee' => (int) $item->jp_employee,
                    'loan_deduction' => (int) ($item->loanInstallment ? $item->loanInstallment->amount : 0),
                    'other_deductions' => (int) $item->other_deductions,
                ],

                // Company Benefits (BPJS dibayar perusahaan)
                'company_benefits' => [
                    'bpjs_kesehatan_company' => (int) $item->bpjs_kesehatan_company,
                    'jht_company' => (int) $item->jht_company,
                    'jp_company' => (int) $item->jp_company,
                    'jkk_company' => (int) $item->jkk_company,
                    'jkm_company' => (int) $item->jkm_company,
                ],

                'remarks' => $item->remarks,
                'download_url' => route('api.employee.payrolls.download', $item->id),
            ];
        });

        return $this->paginatedResponse($payrollItems, 'Daftar slip gaji berhasil diambil.');
    }

    /**
     * Download or stream PDF slip gaji for the authenticated employee.
     */
    public function download(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $company = $employee->company;
        $item = PayrollItem::with([
            'payroll',
            'employee.position',
            'employee.division',
            'employee.company',
            'reimbursements',
        ])
        ->where('id', $id)
        ->where('employee_id', $employee->id)
        ->firstOrFail();

        $payroll = $item->payroll;
        // Inject single item so the template renders this specific slip
        $payroll->setRelation('items', collect([$item]));

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $pdf = Pdf::loadView('pdf.payroll-slip', compact('payroll', 'company', 'bpjsTk', 'bpjsKes'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Slip-Gaji-' . Str::slug($employee->name) . '-' . $payroll->code . '.pdf';
        return $pdf->stream($fileName);
    }
}
