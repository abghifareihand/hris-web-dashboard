<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get Attendance History for logged in employee
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $query = Attendance::with('shift')
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('attendance_status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        $attendances = $query->latest('date')->latest('created_at')->paginate($this->getPerPage($request));

        $attendances->getCollection()->transform(function ($attendance) use ($employee) {
            return [
                'id' => $attendance->id,
                'name' => $employee->user ? $employee->user->name : ($employee->name ?? null),
                'date' => $attendance->date ? Carbon::parse($attendance->date)->format('Y-m-d') : null,
                'shift' => $attendance->shift ? [
                    'id' => $attendance->shift->id,
                    'name' => $attendance->shift->name,
                    'clock_in' => $attendance->shift->clock_in,
                    'clock_out' => $attendance->shift->clock_out,
                ] : null,
                'clock_in_time' => $attendance->clock_in_time ? Carbon::parse($attendance->clock_in_time)->format('H:i:s') : null,
                'clock_out_time' => $attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time)->format('H:i:s') : null,
                'clock_in_photo_url' => $attendance->clock_in_photo ? asset('storage/' . $attendance->clock_in_photo) : null,
                'clock_out_photo_url' => $attendance->clock_out_photo ? asset('storage/' . $attendance->clock_out_photo) : null,
                'clock_in_latitude' => $attendance->clock_in_latitude ? (string) $attendance->clock_in_latitude : null,
                'clock_in_longitude' => $attendance->clock_in_longitude ? (string) $attendance->clock_in_longitude : null,
                'clock_in_address' => $attendance->clock_in_address,
                'clock_out_latitude' => $attendance->clock_out_latitude ? (string) $attendance->clock_out_latitude : null,
                'clock_out_longitude' => $attendance->clock_out_longitude ? (string) $attendance->clock_out_longitude : null,
                'clock_out_address' => $attendance->clock_out_address,
                'late_minutes' => (int) $attendance->late_minutes,
                'early_leaving_minutes' => (int) $attendance->early_leaving_minutes,
                'total_work_minutes' => (int) $attendance->total_work_minutes,
                'total_violation_minutes' => (int) $attendance->total_violation_minutes,
                'attendance_status' => $attendance->attendance_status,
                'attendance_status_label' => $attendance->status_label,
                'notes' => $attendance->notes,
                'created_at' => $attendance->created_at ? $attendance->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return $this->paginatedResponse($attendances, 'Riwayat absensi berhasil diambil.');
    }
}
