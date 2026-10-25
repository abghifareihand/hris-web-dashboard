<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReimbursementController extends Controller
{
    /**
     * Get list of reimbursements for the owner's company
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

        $query = Reimbursement::with(['employee.user', 'approvedBy:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reimbursements = $query->latest('date')->latest('id')->paginate($this->getPerPage($request));

        $reimbursements->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->employee ? $item->employee->name : null,
                'date' => Carbon::parse($item->date)->format('Y-m-d'),
                'amount' => (int) $item->amount,
                'reason' => $item->reason,
                'attachment' => $item->attachment,
                'attachment_url' => $item->attachment ? asset('storage/' . $item->attachment) : null,
                'status' => $item->status, // 'pending', 'approved', 'paid', 'rejected', 'cancelled'
                'payout_method' => $item->payout_method, // 'payroll', 'direct'
                'reject_reason' => $item->reject_reason,
                'approved_by_name' => $item->approvedBy ? $item->approvedBy->name : null,
                'approved_at' => $item->approved_at ? Carbon::parse($item->approved_at)->format('Y-m-d H:i') : null,
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return $this->paginatedResponse($reimbursements, 'Daftar klaim biaya berhasil diambil.');
    }

    /**
     * Approve a pending reimbursement request
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

        $reimbursement = Reimbursement::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$reimbursement) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan klaim biaya pending tidak ditemukan.'
            ], 404);
        }

        $payoutMethod = $request->input('payout_method', 'payroll');
        if (!in_array($payoutMethod, ['payroll', 'direct'])) {
            $payoutMethod = 'payroll';
        }

        $reimbursement->update([
            'status' => $payoutMethod === 'direct' ? 'paid' : 'approved',
            'payout_method' => $payoutMethod,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($reimbursement->employee) {
            \App\Services\NotificationService::sendToEmployee($reimbursement->employee, [
                'title' => 'Klaim Reimbursement Disetujui',
                'message' => 'Pengajuan reimbursement "' . ($reimbursement->title ?? 'Biaya Operasional') . '" sebesar Rp ' . number_format($reimbursement->amount, 0, ',', '.') . ' telah disetujui.',
                'type' => 'reimbursement',
                'route' => 'reimbursement_list',
                'entity_id' => $reimbursement->id,
                'entity_type' => 'reimbursement',
                'icon' => 'receipt',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => $payoutMethod === 'direct'
                ? 'Pengajuan klaim biaya berhasil disetujui dan ditandai lunas (Transfer Langsung).'
                : 'Pengajuan klaim biaya berhasil disetujui untuk dicairkan bersama gaji (Payroll).'
        ]);
    }

    /**
     * Reject a pending reimbursement request
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

        $reimbursement = Reimbursement::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$reimbursement) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan klaim biaya pending tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => 'required|string|max:500'
        ], [
            'reject_reason.required' => 'Alasan penolakan wajib diisi.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $reimbursement->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($reimbursement->employee) {
            \App\Services\NotificationService::sendToEmployee($reimbursement->employee, [
                'title' => 'Klaim Reimbursement Ditolak',
                'message' => 'Pengajuan reimbursement "' . ($reimbursement->title ?? 'Biaya Operasional') . '" telah ditolak. Alasan: ' . $request->reject_reason,
                'type' => 'reimbursement',
                'route' => 'reimbursement_list',
                'entity_id' => $reimbursement->id,
                'entity_type' => 'reimbursement',
                'icon' => 'receipt',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan klaim biaya berhasil ditolak.'
        ]);
    }

    /**
     * Delete a non-pending reimbursement request
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

        $reimbursement = Reimbursement::where('company_id', $company->id)->find($id);

        if (!$reimbursement) {
            return response()->json([
                'status' => false,
                'message' => 'Data klaim biaya tidak ditemukan.'
            ], 404);
        }

        // Only allow deleting non-pending requests
        if ($reimbursement->status === 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan yang masih pending tidak dapat dihapus. Silakan setujui atau tolak terlebih dahulu.'
            ], 400);
        }

        if ($reimbursement->status === 'paid' || !empty($reimbursement->payroll_item_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Klaim biaya yang sudah dibayarkan atau terhubung dengan payroll tidak dapat dihapus.'
            ], 400);
        }

        if ($reimbursement->attachment && Storage::disk('public')->exists($reimbursement->attachment)) {
            Storage::disk('public')->delete($reimbursement->attachment);
        }

        $reimbursement->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data klaim biaya berhasil dihapus.'
        ]);
    }
}
