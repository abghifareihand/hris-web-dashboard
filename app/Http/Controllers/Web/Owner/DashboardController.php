<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveCategory;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Division;
use App\Models\Reimbursement;
use App\Models\Loan;
use App\Models\Announcement;
use App\Models\PendingEmployee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // 1. STATS UTAMA (4 KARTU ATAS)
        $totalEmployees = Employee::where('company_id', $company->id)->count();
        $newEmployeesThisMonth = Employee::where('company_id', $company->id)
            ->whereYear('joined_at', $today->year)
            ->whereMonth('joined_at', $today->month)
            ->count();

        // Hadir Hari Ini (memiliki jam masuk / status kehadiran)
        $todayPresentCount = Attendance::where('company_id', $company->id)
            ->whereDate('date', $todayStr)
            ->where(function ($q) {
                $q->whereNotNull('clock_in_time')
                  ->orWhereIn('attendance_status', ['present', 'good', 'very_good', 'fair', 'late']);
            })
            ->count();
        $attendanceRate = $totalEmployees > 0 ? round(($todayPresentCount / $totalEmployees) * 100, 1) : 0;

        // Cuti Pending
        $pendingLeavesCount = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // Estimasi Payroll Bulan Ini
        $currentMonthPayroll = Payroll::where('company_id', $company->id)
            ->whereYear('start_date', $today->year)
            ->whereMonth('start_date', $today->month)
            ->first();

        if ($currentMonthPayroll && $currentMonthPayroll->total_amount > 0) {
            $estimatedPayrollAmount = $currentMonthPayroll->total_amount;
        } else {
            // Kalkulasi dari gaji pokok + tunjangan tetap karyawan
            $estimatedPayrollAmount = Employee::where('company_id', $company->id)
                ->selectRaw('SUM(basic_salary + fixed_allowance) as total')
                ->value('total') ?? 0;
        }

        // Format short payroll e.g. Rp 112,9 Jt atau Rp 112 Jt
        if ($estimatedPayrollAmount >= 1000000000) {
            $formattedPayroll = 'Rp ' . number_format($estimatedPayrollAmount / 1000000000, 1, ',', '.') . ' M';
        } elseif ($estimatedPayrollAmount >= 1000000) {
            $formattedPayroll = 'Rp ' . number_format($estimatedPayrollAmount / 1000000, 1, ',', '.') . ' Jt';
        } else {
            $formattedPayroll = 'Rp ' . number_format($estimatedPayrollAmount, 0, ',', '.');
        }

        // 2. STATISTIK 30 HARI TERAKHIR (3 KARTU)
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Rata-rata jam kerja per hari (dari absensi yang memiliki clock_in dan clock_out)
        $avgWorkMinutes = Attendance::where('company_id', $company->id)
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->whereNotNull('clock_in_time')
            ->whereNotNull('clock_out_time')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, clock_in_time, clock_out_time)) as avg_min')
            ->value('avg_min');
        $avgWorkHours = $avgWorkMinutes ? number_format($avgWorkMinutes / 60, 1, '.', '') : '8.0';

        // Total Lembur (Overtime yang approved dalam 30 hari)
        $totalOvertimeMinutes = Overtime::where('company_id', $company->id)
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->where('status', 'approved')
            ->sum('duration_minutes');
        $totalOvertimeHours = round($totalOvertimeMinutes / 60);

        // Keterlambatan (jumlah kejadian terlambat dalam 30 hari)
        $totalLatenessCount = Attendance::where('company_id', $company->id)
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->where('late_minutes', '>', 0)
            ->count();

        // 3. CHARTS DATA (100% DINAMIS DARI DB)
        // Chart 1: Trend Kehadiran 14 Hari Terakhir
        $attendanceLabels = [];
        $attendanceDataHadir = [];
        $attendanceDataTerlambat = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $dStr = $d->toDateString();
            $attendanceLabels[] = $d->translatedFormat('d M');

            $hadir = Attendance::where('company_id', $company->id)
                ->whereDate('date', $dStr)
                ->whereNotNull('clock_in_time')
                ->where('late_minutes', 0)
                ->count();
            $terlambat = Attendance::where('company_id', $company->id)
                ->whereDate('date', $dStr)
                ->where('late_minutes', '>', 0)
                ->count();

            $attendanceDataHadir[] = $hadir;
            $attendanceDataTerlambat[] = $terlambat;
        }

        // Chart 2: Karyawan per Divisi (Doughnut)
        $divisions = Division::where('company_id', $company->id)
            ->withCount('employees')
            ->get();
        
        $deptLabels = [];
        $deptData = [];
        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#6366f1'];
        $deptColors = [];
        $colorIdx = 0;

        foreach ($divisions as $div) {
            $deptLabels[] = $div->name;
            $deptData[] = $div->employees_count;
            $deptColors[] = $palette[$colorIdx % count($palette)];
            $colorIdx++;
        }

        if (empty($deptLabels)) {
            $deptLabels = ['Umum'];
            $deptData = [$totalEmployees];
            $deptColors = ['#3b82f6'];
        }

        // Chart 3: Trend Payroll 6 Bulan Terakhir (Bar)
        $payrollLabels = [];
        $payrollData = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $payrollLabels[] = $m->translatedFormat('M Y');

            $pAmount = Payroll::where('company_id', $company->id)
                ->whereYear('start_date', $m->year)
                ->whereMonth('start_date', $m->month)
                ->sum('total_amount');

            // Jika bulan ini belum closing atau 0, gunakan estimasi dari total karyawan
            if ($pAmount == 0 && $i == 0) {
                $pAmount = $estimatedPayrollAmount;
            }

            // Convert to Juta Rp
            $payrollData[] = round($pAmount / 1000000, 1);
        }

        // Chart 4: Statistik Cuti Tahun Ini (Doughnut)
        $leaveCategories = LeaveCategory::where('company_id', $company->id)->get();
        $leaveLabels = [];
        $leaveData = [];
        $leaveColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        $leaveColorsSelected = [];
        $lColorIdx = 0;

        foreach ($leaveCategories as $cat) {
            $count = LeaveRequest::where('company_id', $company->id)
                ->where('leave_category_id', $cat->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $today->year)
                ->count();
            $leaveLabels[] = $cat->name;
            $leaveData[] = $count;
            $leaveColorsSelected[] = $leaveColors[$lColorIdx % count($leaveColors)];
            $lColorIdx++;
        }

        if (empty($leaveLabels) || array_sum($leaveData) == 0) {
            $approvedLeavesCount = LeaveRequest::where('company_id', $company->id)->where('status', 'approved')->count();
            $leaveLabels = ['Cuti Tahunan', 'Lainnya'];
            $leaveData = [$approvedLeavesCount, 0];
            $leaveColorsSelected = ['#3b82f6', '#94a3b8'];
        }

        // Chart 5: Statistik Keterlambatan 30 Hari (Doughnut)
        $lateCountsPerEmployee = Attendance::where('company_id', $company->id)
            ->where('date', '>=', $thirtyDaysAgo->toDateString())
            ->where('late_minutes', '>', 0)
            ->selectRaw('employee_id, count(*) as total_late')
            ->groupBy('employee_id')
            ->pluck('total_late', 'employee_id');

        $onTimeCount = 0;
        $late1to3Count = 0;
        $lateOver3Count = 0;

        $allCompanyEmpIds = Employee::where('company_id', $company->id)->pluck('id');
        foreach ($allCompanyEmpIds as $eId) {
            $lCount = $lateCountsPerEmployee[$eId] ?? 0;
            if ($lCount == 0) {
                $onTimeCount++;
            } elseif ($lCount <= 3) {
                $late1to3Count++;
            } else {
                $lateOver3Count++;
            }
        }

        $chartData = [
            'attendance' => [
                'labels' => $attendanceLabels,
                'data_hadir' => $attendanceDataHadir,
                'data_terlambat' => $attendanceDataTerlambat,
            ],
            'payroll' => [
                'labels' => $payrollLabels,
                'data' => $payrollData,
            ],
            'department' => [
                'labels' => $deptLabels,
                'data' => $deptData,
                'colors' => $deptColors,
            ],
            'leave' => [
                'labels' => $leaveLabels,
                'data' => $leaveData,
                'colors' => $leaveColorsSelected,
            ],
            'late' => [
                'labels' => ['Tepat Waktu', 'Terlambat 1-3x', 'Terlambat >3x'],
                'data' => [$onTimeCount, $late1to3Count, $lateOver3Count],
                'colors' => ['#10b981', '#f59e0b', '#ef4444'],
            ]
        ];

        $stats30Days = [
            'avg_work_hours' => $avgWorkHours,
            'total_overtime' => $totalOvertimeHours,
            'total_lateness' => $totalLatenessCount,
        ];

        // 4. AKTIVITAS HARI INI
        $todayAttendanceBreakdown = [
            'present' => Attendance::where('company_id', $company->id)->whereDate('date', $todayStr)->where('attendance_status', 'present')->count(),
            'late' => Attendance::where('company_id', $company->id)->whereDate('date', $todayStr)->where('attendance_status', 'late')->count(),
            'leave' => LeaveRequest::where('company_id', $company->id)->where('status', 'approved')->whereDate('start_date', '<=', $todayStr)->whereDate('end_date', '>=', $todayStr)->count(),
            'alpha' => Attendance::where('company_id', $company->id)->whereDate('date', $todayStr)->where('attendance_status', 'alpha')->count(),
        ];

        // Clock-ins terbaru hari ini
        $recentClockIns = Attendance::with(['employee.division'])
            ->where('company_id', $company->id)
            ->whereDate('date', $todayStr)
            ->whereNotNull('clock_in_time')
            ->orderBy('clock_in_time', 'desc')
            ->take(5)
            ->get();

        // Karyawan terbaru yang bergabung
        $recentEmployees = Employee::with('division')
            ->where('company_id', $company->id)
            ->orderBy('joined_at', 'desc')
            ->take(5)
            ->get();

        // Ulang tahun hari ini
        $birthdaysToday = Employee::with('division')
            ->where('company_id', $company->id)
            ->whereMonth('birth_date', $today->month)
            ->whereDay('birth_date', $today->day)
            ->get();

        // Karyawan cuti hari ini
        $leavesToday = LeaveRequest::with(['employee.division', 'leaveCategory'])
            ->where('company_id', $company->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->get();

        // 5. WIDGET PENGEMBANGAN BARU (NILAI TAMBAH OWNER)
        // Action Required Summary (Item yang butuh persetujuan)
        $pendingApprovals = [
            'employees' => PendingEmployee::where('company_id', $company->id)->where('status', 'pending')->count(),
            'leaves' => LeaveRequest::where('company_id', $company->id)->where('status', 'pending')->count(),
            'overtimes' => Overtime::where('company_id', $company->id)->where('status', 'pending')->count(),
            'reimbursements' => Reimbursement::where('company_id', $company->id)->where('status', 'pending')->count(),
            'loans' => Loan::where('company_id', $company->id)->where('status', 'pending')->count(),
        ];
        $totalPendingApprovals = array_sum($pendingApprovals);

        // Pengumuman Aktif
        $activeAnnouncements = Announcement::with(['branch', 'division'])
            ->where('company_id', $company->id)
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return Inertia::render('Owner/Dashboard/Index', [
            'totalEmployees' => $totalEmployees,
            'newEmployeesThisMonth' => $newEmployeesThisMonth,
            'todayPresentCount' => $todayPresentCount,
            'attendanceRate' => $attendanceRate,
            'pendingLeavesCount' => $pendingLeavesCount,
            'formattedPayroll' => $formattedPayroll,
            'chartData' => $chartData,
            'stats30Days' => $stats30Days,
            'todayAttendanceBreakdown' => $todayAttendanceBreakdown,
            'recentClockIns' => $recentClockIns,
            'recentEmployees' => $recentEmployees,
            'birthdaysToday' => $birthdaysToday,
            'leavesToday' => $leavesToday,
            'pendingApprovals' => $pendingApprovals,
            'totalPendingApprovals' => $totalPendingApprovals,
            'activeAnnouncements' => $activeAnnouncements,
        ]);
    }
}
