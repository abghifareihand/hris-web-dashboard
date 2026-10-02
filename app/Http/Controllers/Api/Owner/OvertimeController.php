<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    /**
     * Get list of overtimes for the owner's company
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

        $query = Overtime::with(['employee', 'approvedBy'])
            ->where('company_id', $company->id);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $overtimes = $query->latest('date')->latest('created_at')->paginate($this->getPerPage($request));

        $overtimes->getCollection()->transform(function ($overtime) {
            return [
                'id' => $overtime->id,
                'title' => $overtime->title,
                'date' => $overtime->date->format('Y-m-d'),
                'start_time' => Carbon::parse($overtime->start_time)->format('H:i'),
                'end_time' => Carbon::parse($overtime->end_time)->format('H:i'),
                'duration_minutes' => $overtime->duration_minutes,
                'description' => $overtime->description,
                'status' => $overtime->status,
                'reject_reason' => $overtime->reject_reason,
                'created_at' => $overtime->created_at->format('Y-m-d H:i:s'),
                'employee' => $overtime->employee ? [
                    'id' => $overtime->employee->id,
                    'name' => $overtime->employee->name,
                    'email' => $overtime->employee->email,
                ] : null,
            ];
        });

        return $this->paginatedResponse($overtimes, 'Data lembur berhasil diambil.');
    }

    /**
     * Approve a pending overtime request
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

        $overtime = Overtime::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan lembur pending tidak ditemukan.'
            ], 404);
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan lembur berhasil disetujui.'
        ]);
    }

    /**
     * Reject a pending overtime request
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

        $overtime = Overtime::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan lembur pending tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Alasan penolakan wajib diisi.',
                'errors' => $validator->errors()
            ], 422);
        }

        $overtime->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan lembur berhasil ditolak.'
        ]);
    }

    /**
     * Delete an overtime request
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

        $overtime = Overtime::where('company_id', $company->id)->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan lembur tidak ditemukan.'
            ], 404);
        }

        // Only allow deleting non-pending requests (approved, rejected, or cancelled)
        if ($overtime->status === 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan yang masih pending tidak dapat dihapus. Silakan setujui/tolak terlebih dahulu.'
            ], 400);
        }

        $overtime->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data pengajuan lembur berhasil dihapus.'
        ]);
    }
}
