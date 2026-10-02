<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    /**
     * Get balances for the current year
     */
    public function getBalances(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $currentYear = date('Y');

        $balances = LeaveBalance::with('leaveCategory')
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('year', $currentYear)
            ->get();

        $data = $balances->map(function ($balance) {
            return [
                'id' => $balance->id,
                'leave_category_id' => $balance->leaveCategory->id,
                'category_name' => $balance->leaveCategory->name,
                'quota' => $balance->quota,
                'used' => $balance->used,
                'remaining' => $balance->quota - $balance->used,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data sisa cuti',
            'data' => $data
        ]);
    }

    /**
     * Get list of leave requests
     */
    public function getRequests(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $query = LeaveRequest::with('leaveCategory')
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status); // pending, approved, rejected, cancelled
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        $requests = $query->latest()->paginate($this->getPerPage($request));

        $requests->getCollection()->transform(function ($req) use ($employee) {
            // Get remaining balance for the category and year of the request
            $year = date('Y', strtotime($req->start_date));
            $balance = \App\Models\LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_category_id', $req->leave_category_id)
                ->where('year', $year)
                ->first();

            $remainingBalance = 0;
            if ($balance) {
                $remainingBalance = $balance->quota - $balance->used;
            }

            return [
                'id' => $req->id,
                'employee_name' => $employee->name,
                'leave_category' => $req->leaveCategory->name,
                'remaining_balance' => $remainingBalance,
                'start_date' => $req->start_date,
                'end_date' => $req->end_date,
                'days_count' => $req->days_count,
                'status' => $req->status,
                'reason' => $req->reason,
                'reject_reason' => $req->reject_reason,
                'attachment_url' => $req->attachment ? asset('storage/' . $req->attachment) : null,
                'created_at' => $req->created_at,
            ];
        });

        return $this->paginatedResponse($requests, 'Berhasil mengambil data pengajuan cuti');
    }

    /**
     * Store a new leave request
     */
    public function storeRequest(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'leave_category_id' => 'required|exists:leave_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_count' => 'required|integer|min:1',
            'reason' => 'nullable|string',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Check category company
        $category = LeaveCategory::where('company_id', $employee->company_id)
            ->where('id', $request->leave_category_id)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Kategori cuti tidak valid.'
            ], 400);
        }

        // Check for overlapping leave requests
        $exists = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah memiliki pengajuan cuti aktif (pending/disetujui) pada rentang tanggal tersebut.'
            ], 400);
        }

        // Check balance
        $currentYear = date('Y', strtotime($request->start_date));
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_category_id', $category->id)
            ->where('year', $currentYear)
            ->first();

        if (!$balance) {
            return response()->json([
                'status' => false,
                'message' => 'Saldo cuti untuk kategori ini tidak ditemukan pada tahun ' . $currentYear
            ], 400);
        }

        $remaining = $balance->quota - $balance->used;
        if ($request->days_count > $remaining) {
            return response()->json([
                'status' => false,
                'message' => 'Sisa cuti tidak mencukupi.',
                'remaining' => $remaining,
                'requested' => $request->days_count
            ], 400);
        }

        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leaves', 'public');
        }

        $leaveRequest = LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_category_id' => $category->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_count' => $request->days_count,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending' // default
        ]);

        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new \App\Notifications\NewEmployeeLeavePendingRequest($leaveRequest));
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan cuti berhasil dikirim',
        ], 201);
    }

    /**
     * Cancel a leave request
     */
    public function cancelRequest(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan cuti tidak ditemukan.'
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya pengajuan dengan status pending yang dapat dibatalkan.'
            ], 400);
        }

        $leaveRequest->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan cuti berhasil dibatalkan.'
        ]);
    }
}
