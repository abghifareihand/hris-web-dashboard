<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    /**
     * Get list of loans for the owner's company
     * Filters: employee_id, status, start_date, end_date, date, search
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

        $query = Loan::with([
            'employee:id,name,payroll_cycle',
            'installments' => function ($q) {
                $q->orderBy('due_date', 'asc');
            }
        ])->where('company_id', $company->id);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        $loans = $query->latest('date')->latest('id')->paginate($this->getPerPage($request));

        $loans->getCollection()->transform(function ($loan) {
            $paidAmount = $loan->installments->where('status', 'paid')->sum('amount');
            $remainingAmount = $loan->installments->where('status', 'unpaid')->sum('amount');
            $paidCount = $loan->installments->where('status', 'paid')->count();
            $totalCount = $loan->installments->count();

            return [
                'id' => $loan->id,
                'name' => $loan->employee ? $loan->employee->name : null,
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
                'created_at' => $loan->created_at ? $loan->created_at->format('Y-m-d H:i:s') : null,

                // Detailed installments schedule included directly
                'installments' => $loan->installments->map(function ($inst, $index) {
                    return [
                        'id' => $inst->id,
                        'installment_number' => $index + 1,
                        'due_date' => Carbon::parse($inst->due_date)->format('Y-m-d'),
                        'amount' => (int) $inst->amount,
                        'status' => $inst->status, // 'unpaid', 'paid'
                        'updated_at' => $inst->updated_at ? $inst->updated_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values(),
            ];
        });

        return $this->paginatedResponse($loans, 'Daftar pinjaman berhasil diambil.');
    }

    /**
     * Create a new loan directly by Owner
     */
    public function store(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1000',
            'tenor' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string',
        ], [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'employee_id.exists' => 'Karyawan tidak valid.',
            'date.required' => 'Tanggal pinjaman wajib diisi.',
            'amount.required' => 'Nominal pinjaman wajib diisi.',
            'tenor.required' => 'Tenor pinjaman wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = Employee::where('company_id', $company->id)->findOrFail($request->employee_id);

        $loan = Loan::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'tenor' => $request->tenor,
            'description' => $request->description,
            'status' => 'approved',
        ]);

        $loan->generateInstallments();

        return response()->json([
            'status' => true,
            'message' => 'Pinjaman berhasil dibuat dan disetujui.',
        ], 201);
    }

    /**
     * Approve a pending loan request
     */
    public function approve(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $loan = Loan::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$loan) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan pinjaman pending tidak ditemukan.'
            ], 404);
        }

        $loan->update(['status' => 'approved']);

        $loan->generateInstallments();

        if ($loan->employee) {
            \App\Services\NotificationService::sendToEmployee($loan->employee, [
                'title' => 'Pengajuan Pinjaman Disetujui',
                'message' => 'Pengajuan pinjaman kasbon Anda sebesar Rp ' . number_format($loan->amount, 0, ',', '.') . ' telah disetujui.',
                'type' => 'loan',
                'route' => 'loan_list',
                'entity_id' => $loan->id,
                'entity_type' => 'loan',
                'icon' => 'wallet',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan pinjaman berhasil disetujui.'
        ]);
    }

    /**
     * Reject a pending loan request
     */
    public function reject(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $loan = Loan::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$loan) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan pinjaman pending tidak ditemukan.'
            ], 404);
        }

        $loan->update(['status' => 'rejected']);

        if ($loan->employee) {
            \App\Services\NotificationService::sendToEmployee($loan->employee, [
                'title' => 'Pengajuan Pinjaman Ditolak',
                'message' => 'Pengajuan pinjaman kasbon Anda sebesar Rp ' . number_format($loan->amount, 0, ',', '.') . ' telah ditolak.',
                'type' => 'loan',
                'route' => 'loan_list',
                'entity_id' => $loan->id,
                'entity_type' => 'loan',
                'icon' => 'wallet',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan pinjaman berhasil ditolak.'
        ]);
    }

    /**
     * Delete a loan request
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

        $loan = Loan::where('company_id', $company->id)->find($id);
        if (!$loan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pinjaman tidak ditemukan.'
            ], 404);
        }

        $hasPaidInstallment = $loan->installments()->where('status', 'paid')->exists();
        if ($hasPaidInstallment) {
            return response()->json([
                'status' => false,
                'message' => 'Pinjaman yang sudah memiliki riwayat pembayaran tidak dapat dihapus.'
            ], 400);
        }

        $installmentIds = $loan->installments()->pluck('id');
        $isAttachedToPayroll = \App\Models\PayrollItem::whereIn('loan_installment_id', $installmentIds)->exists();
        if ($isAttachedToPayroll) {
            return response()->json([
                'status' => false,
                'message' => 'Pinjaman ini memiliki cicilan yang sedang diproses dalam penggajian dan tidak dapat dihapus.'
            ], 400);
        }

        $loan->installments()->delete();
        $loan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data pinjaman berhasil dihapus.'
        ]);
    }

    /**
     * Mark an installment as paid or unpaid
     */
    public function updateInstallment(Request $request, $id, $installmentId)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $loan = Loan::where('company_id', $company->id)->find($id);
        if (!$loan) {
            return response()->json([
                'status' => false,
                'message' => 'Data pinjaman tidak ditemukan.'
            ], 404);
        }

        $installment = LoanInstallment::where('loan_id', $loan->id)->find($installmentId);
        if (!$installment) {
            return response()->json([
                'status' => false,
                'message' => 'Data cicilan tidak ditemukan.'
            ], 404);
        }

        $targetStatus = $request->input('status');
        if ($targetStatus && in_array($targetStatus, ['paid', 'unpaid'])) {
            $installment->status = $targetStatus;
        } else {
            // Default to paid if currently unpaid, or toggle
            $installment->status = $installment->status === 'paid' ? 'unpaid' : 'paid';
        }
        $installment->save();

        // Update overall loan status
        $unpaidCount = $loan->installments()->where('status', 'unpaid')->count();
        $loan->status = $unpaidCount === 0 ? 'paid' : 'approved';
        $loan->save();

        $statusMsg = $installment->status === 'paid' ? 'lunas' : 'belum lunas';

        return response()->json([
            'status' => true,
            'message' => "Status cicilan berhasil diubah menjadi {$statusMsg}."
        ]);
    }
}
