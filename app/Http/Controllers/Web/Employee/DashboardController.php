<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Loan;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee()
            ->with(['branch:id,name', 'division:id,name', 'position:id,name', 'company:id,name_company'])
            ->first();

        if (!$employee) {
            return Inertia::render('Employee/Dashboard/Index', [
                'hasProfile' => false,
                'employee' => null,
                'stats' => [],
                'todayAttendance' => null,
                'todaySchedule' => null,
                'announcements' => [],
                'recentRequests' => [],
            ]);
        }

        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 1. Today's Attendance & Schedule
        $todayAttendance = Attendance::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $todaySchedule = WorkSchedule::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // 2. Month Attendance Summary
        $presentDays = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('attendance_status', ['alpha', 'absent'])
            ->whereNotNull('clock_in_time')
            ->count();

        $scheduledDays = WorkSchedule::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('is_day_off', false)
            ->count();

        if ($scheduledDays === 0) {
            // Default 22 work days estimation
            $scheduledDays = 22;
        }

        $attendanceRate = $scheduledDays > 0 ? min(100, round(($presentDays / $scheduledDays) * 100)) : 100;

        // 3. Leave Balance
        $totalLeaveBalance = (int) LeaveBalance::where('employee_id', $employee->id)
            ->where('year', Carbon::now()->year)
            ->selectRaw('COALESCE(SUM(quota - used), 0) as remaining')
            ->value('remaining');

        // 4. Overtime Hours
        $otMinutes = Overtime::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('duration_minutes');
        $otHours = round($otMinutes / 60, 1);

        // 5. Pending Requests
        $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)->where('status', 'pending')->count();
        $pendingOvertimes = Overtime::where('employee_id', $employee->id)->where('status', 'pending')->count();
        $pendingReimbursements = Reimbursement::where('employee_id', $employee->id)->where('status', 'pending')->count();
        $pendingLoans = Loan::where('employee_id', $employee->id)->where('status', 'pending')->count();
        $totalPending = $pendingLeaves + $pendingOvertimes + $pendingReimbursements + $pendingLoans;

        // 6. Announcements (active for employee's branch/division/global)
        $announcements = Announcement::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(function ($q) use ($employee) {
                $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('division_id')->orWhere('division_id', $employee->division_id);
            })
            ->latest()
            ->take(3)
            ->get();

        // 7. Recent Requests across modules
        $recentLeaves = LeaveRequest::with('leaveCategory:id,name')
            ->where('employee_id', $employee->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($l) => [
                'type' => 'Cuti',
                'title' => $l->leaveCategory->name ?? 'Pengajuan Cuti',
                'detail' => Carbon::parse($l->start_date)->format('d M') . ' - ' . Carbon::parse($l->end_date)->format('d M Y'),
                'status' => $l->status,
                'created_at' => $l->created_at,
            ]);

        $recentOvertimes = Overtime::where('employee_id', $employee->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($o) => [
                'type' => 'Lembur',
                'title' => 'Lembur ' . round($o->duration_minutes / 60, 1) . ' Jam',
                'detail' => Carbon::parse($o->date)->format('d M Y'),
                'status' => $o->status,
                'created_at' => $o->created_at,
            ]);

        $recentReimbursements = Reimbursement::where('employee_id', $employee->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($r) => [
                'type' => 'Reimbursement',
                'title' => $r->title,
                'detail' => 'Rp ' . number_format($r->amount, 0, ',', '.'),
                'status' => $r->status,
                'created_at' => $r->created_at,
            ]);

        $recentRequests = $recentLeaves->concat($recentOvertimes)->concat($recentReimbursements)
            ->sortByDesc('created_at')
            ->values()
            ->take(5);

        return Inertia::render('Employee/Dashboard/Index', [
            'hasProfile' => true,
            'employee' => $employee,
            'stats' => [
                'present_days' => $presentDays,
                'scheduled_days' => $scheduledDays,
                'attendance_rate' => $attendanceRate,
                'leave_balance' => $totalLeaveBalance,
                'overtime_hours' => $otHours,
                'pending_requests' => $totalPending,
            ],
            'todayAttendance' => $todayAttendance,
            'todaySchedule' => $todaySchedule,
            'announcements' => $announcements,
            'recentRequests' => $recentRequests,
        ]);
    }
}
