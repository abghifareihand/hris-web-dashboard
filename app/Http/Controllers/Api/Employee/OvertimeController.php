<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    /**
     * Get list of overtimes for the authenticated employee
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

        $query = Overtime::where('employee_id', $employee->id);

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
            ];
        });

        return $this->paginatedResponse($overtimes, 'Overtimes retrieved successfully');
    }

    /**
     * Store a new overtime request
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if there is already a pending or approved overtime request on the same date
        $exists = Overtime::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('date', $request->date)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah memiliki pengajuan lembur aktif (pending/disetujui) pada tanggal tersebut.'
            ], 400);
        }

        $start = Carbon::parse($request->date . ' ' . $request->start_time);
        $end = Carbon::parse($request->date . ' ' . $request->end_time);

        // Handle case where end time is on the next day (e.g. start 19:00, end 02:00)
        if ($end->lt($start)) {
            $end->addDay();
        }

        $durationMinutes = $start->diffInMinutes($end);

        if ($durationMinutes <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Waktu selesai harus lebih besar dari waktu mulai.'
            ], 422);
        }

        $overtime = Overtime::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'title' => $request->title,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $durationMinutes,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new \App\Notifications\NewEmployeeOvertimePendingRequest($overtime));
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan lembur berhasil dibuat.'
        ], 201);
    }

    /**
     * Cancel a pending overtime request
     */
    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;
        
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $overtime = Overtime::where('employee_id', $employee->id)->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => 'Data lembur tidak ditemukan.'
            ], 404);
        }

        if ($overtime->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya pengajuan yang masih menunggu (pending) yang dapat dibatalkan.'
            ], 422);
        }

        $overtime->update(['status' => 'cancelled']);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan lembur berhasil dibatalkan.'
        ]);
    }
}
