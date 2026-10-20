<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\LoanInstallment;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Reimbursement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    /**
     * Get list of payrolls for owner's company.
     * Filters: status, year, month, search
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $query = Payroll::with([
            'branch:id,name',
            'division:id,name',
        ])->where('company_id', $company->id);

        // Filter by Status (process, paid, cancelled)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Year
        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        // Optional Filter by Month
        if ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month);
        }

        // Optional Search by Code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%");
        }

        $payrolls = $query->latest('start_date')->latest('id')->paginate($this->getPerPage($request));

        // Single query agregasi summary untuk seluruh payroll yang ada di halaman aktif
        $payrollIds = $payrolls->getCollection()->pluck('id');
        $summaries = PayrollItem::whereIn('payroll_id', $payrollIds)
            ->selectRaw('payroll_id,
                COALESCE(SUM(net_salary), 0) as total_net_salary,
                COALESCE(SUM(total_earnings), 0) as total_earnings,
                COALESCE(SUM(total_deductions), 0) as total_deductions,
                COALESCE(SUM(total_benefits), 0) as total_benefits
            ')
            ->groupBy('payroll_id')
            ->get()
            ->keyBy('payroll_id');

        $payrolls->getCollection()->transform(function ($payroll) use ($summaries) {
            $sum = $summaries->get($payroll->id);

            return [
                'id' => $payroll->id,
                'code' => $payroll->code,
                'start_date' => Carbon::parse($payroll->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($payroll->end_date)->format('Y-m-d'),
                'status' => $payroll->status,
                'paid_at' => $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('Y-m-d H:i') : null,
                'total_employees' => (int) $payroll->total_employees,
                'total_amount' => (int) $payroll->total_amount,
                'branch' => $payroll->branch?->name ?? 'Semua Cabang',
                'division' => $payroll->division?->name ?? 'Semua Divisi',
                'created_at' => $payroll->created_at ? Carbon::parse($payroll->created_at)->format('Y-m-d H:i') : null,
                'summary' => [
                    'total_net_salary' => (int) ($sum?->total_net_salary ?? 0),
                    'total_earnings' => (int) ($sum?->total_earnings ?? 0),
                    'total_deductions' => (int) ($sum?->total_deductions ?? 0),
                    'total_benefits' => (int) ($sum?->total_benefits ?? 0),
                ],
            ];
        });

        return $this->paginatedResponse($payrolls, 'Daftar penggajian berhasil diambil.');
    }

    /**
     * Get paginated employee slip summaries within a payroll batch.
     * Supports search by employee name or nip.
     */
    public function items(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $payroll = Payroll::where('company_id', $company->id)->find($id);
        if (!$payroll) {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian tidak ditemukan.'
            ], 404);
        }

        $itemsQuery = $payroll->items()->with([
            'employee.position:id,name',
            'employee.division:id,name',
            'employee.branch:id,name',
            'loanInstallment',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $itemsQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $payrollItems = $itemsQuery->paginate($this->getPerPage($request));

        $payrollItems->getCollection()->transform(function ($item) use ($payroll) {
            $employee = $item->employee;
            $cycle = $employee?->payroll_cycle ?? $payroll->payroll_cycle ?? 'monthly';
            $basicSalary = (int) $item->basic_salary;
            $totalEarnings = (int) $item->total_earnings;
            $additionalEarnings = max(0, $totalEarnings - $basicSalary);
            $hasLoan = (bool) ($item->loan_installment_id || $item->loanInstallment);
            $hasProrata = (bool) ($item->remarks && str_contains(strtolower($item->remarks), 'prorata'));

            return [
                'id' => $item->id,
                'payroll_id' => $item->payroll_id,
                'employee_id' => $item->employee_id,
                'employee_name' => $employee?->name,
                'employee_nip' => $employee?->nip,
                'position' => $employee?->position?->name,
                'division' => $employee?->division?->name,
                'branch' => $employee?->branch?->name,
                'payroll_cycle' => $cycle,
                'basic_salary' => $basicSalary,
                'additional_earnings' => $additionalEarnings,
                'total_earnings' => $totalEarnings,
                'total_deductions' => (int) $item->total_deductions,
                'net_salary' => (int) $item->net_salary,
                'total_benefits' => (int) $item->total_benefits,
                'has_loan' => $hasLoan,
                'has_prorata' => $hasProrata,
                'remarks' => $item->remarks,
            ];
        });

        return $this->paginatedResponse($payrollItems, 'Daftar item penggajian berhasil diambil.');
    }

    /**
     * Get detailed slip for a single employee within a payroll batch.
     */
    public function showItem(Request $request, $id, $itemId)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $payroll = Payroll::where('company_id', $company->id)->find($id);
        if (!$payroll) {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian tidak ditemukan.'
            ], 404);
        }

        $item = $payroll->items()
            ->with([
                'employee.position:id,name',
                'employee.division:id,name',
                'employee.branch:id,name',
                'employee.company:id,name_company',
                'loanInstallment.loan',
                'reimbursements',
            ])
            ->find($itemId);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Rincian slip gaji karyawan tidak ditemukan.'
            ], 404);
        }

        $employee = $item->employee;
        $cycle = $employee?->payroll_cycle ?? $payroll->payroll_cycle ?? 'monthly';

        return response()->json([
            'status' => true,
            'message' => 'Rincian slip gaji berhasil diambil.',
            'data' => [
                'id' => $item->id,
                'payroll_id' => $item->payroll_id,
                'payroll_code' => $payroll->code,
                'payroll_cycle' => $cycle,
                'period_start' => Carbon::parse($payroll->start_date)->format('Y-m-d'),
                'period_end' => Carbon::parse($payroll->end_date)->format('Y-m-d'),
                'payment_status' => $payroll->status,
                'paid_at' => $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('Y-m-d H:i') : null,

                // Employee info
                'employee' => [
                    'id' => $employee?->id,
                    'name' => $employee?->name,
                    'nip' => $employee?->nip,
                    'position' => $employee?->position?->name,
                    'division' => $employee?->division?->name,
                    'branch' => $employee?->branch?->name,
                    'bank_name' => $employee?->bank_name,
                    'bank_account_number' => $employee?->bank_account_number,
                    'bank_account_name' => $employee?->bank_account_name,
                    'ptkp_status' => $item->ptkp_status ?? $employee?->ptkp_status,
                ],

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
                'download_url' => route('api.owner.payrolls.download-item', ['id' => $payroll->id, 'itemId' => $item->id]),
            ],
        ]);
    }

    /**
     * Download or stream PDF slip gaji for owner view.
     */
    public function downloadItem(Request $request, $id, $itemId)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $payroll = Payroll::where('company_id', $company->id)->find($id);
        if (!$payroll) {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian tidak ditemukan.'
            ], 404);
        }

        $item = $payroll->items()
            ->with([
                'payroll',
                'employee.position',
                'employee.division',
                'employee.company',
                'reimbursements',
            ])
            ->find($itemId);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Rincian slip gaji karyawan tidak ditemukan.'
            ], 404);
        }

        $payroll->setRelation('items', collect([$item]));

        $bpjsTk = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        $bpjsKes = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $pdf = Pdf::loadView('pdf.payroll-slip', compact('payroll', 'company', 'bpjsTk', 'bpjsKes'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Slip-Gaji-' . Str::slug($item->employee?->name ?? 'karyawan') . '-' . $payroll->code . '.pdf';
        return $pdf->stream($fileName);
    }

    /**
     * Mark payroll as paid (Lunas / Selesai).
     * Once paid, it cannot be reverted back to process in mobile.
     */
    public function markAsPaid(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $payroll = Payroll::where('company_id', $company->id)->find($id);

        if (!$payroll) {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian tidak ditemukan.'
            ], 404);
        }

        // Rule: Once paid, it cannot be changed again in mobile
        if ($payroll->status === 'paid') {
            return response()->json([
                'status' => false,
                'message' => 'Penggajian ini sudah berstatus PAID (Lunas) dan tidak dapat diubah kembali.'
            ], 422);
        }

        if ($payroll->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'Penggajian yang sudah dibatalkan tidak dapat langsung ditandai PAID.'
            ], 422);
        }

        DB::transaction(function () use ($payroll) {
            // Update loan installments attached to payroll items
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

            // Update reimbursements attached to payroll items
            foreach ($payroll->items as $item) {
                Reimbursement::where('payroll_item_id', $item->id)->update(['status' => 'paid']);
            }

            // Update payroll status to paid
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Penggajian berhasil ditandai Lunas (PAID).',
            'data' => [
                'id' => $payroll->id,
                'code' => $payroll->code,
                'status' => 'paid',
                'paid_at' => $payroll->paid_at ? Carbon::parse($payroll->paid_at)->format('Y-m-d H:i') : null,
            ],
        ]);
    }

    /**
     * Delete payroll.
     * Can only be deleted if status is 'process'. Cannot delete if already 'paid'.
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $payroll = Payroll::where('company_id', $company->id)->find($id);

        if (!$payroll) {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian tidak ditemukan.'
            ], 404);
        }

        // Rule: If already paid, it cannot be deleted
        if ($payroll->status === 'paid') {
            return response()->json([
                'status' => false,
                'message' => 'Data penggajian yang sudah berstatus PAID tidak dapat dihapus demi integritas pembukuan keuangan.'
            ], 422);
        }

        DB::transaction(function () use ($payroll) {
            // Revert attached loan installments back to unpaid
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

            // Detach and revert reimbursements back to approved
            foreach ($payroll->items as $item) {
                Reimbursement::where('payroll_item_id', $item->id)->update([
                    'payroll_item_id' => null,
                    'status' => 'approved',
                ]);
            }

            // Delete payroll items & payroll
            $payroll->items()->delete();
            $payroll->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Data penggajian berhasil dihapus.'
        ]);
    }
}
