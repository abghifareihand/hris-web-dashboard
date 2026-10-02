<?php

namespace App\Http\Controllers\Web\Owner\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $query = Attendance::with(['employee:id,name,nip,branch_id,division_id', 'employee.division:id,name', 'employee.branch:id,name', 'shift:id,name,clock_in,clock_out'])
            ->where('company_id', $company->id);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('attendance_status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $attendances = $query->latest('date')->paginate(10)->withQueryString();

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        return Inertia::render('Owner/Reports/Attendances/Index', [
            'attendances' => $attendances,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => $request->only(['branch_id', 'division_id', 'status', 'start_date', 'end_date', 'search']),
        ]);
    }

    public function show(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $attendance = Attendance::with([
            'employee:id,name,nip,branch_id,division_id,position_id,phone_number',
            'employee.division:id,name',
            'employee.branch:id,name,latitude,longitude,radius_meters',
            'employee.position:id,name',
            'shift'
        ])
        ->where('company_id', $company->id)
        ->findOrFail($id);

        $schedule = WorkSchedule::where('employee_id', $attendance->employee_id)
            ->where('date', $attendance->date)
            ->first();

        return Inertia::render('Owner/Reports/Attendances/Show', [
            'attendance' => $attendance,
            'schedule' => $schedule,
        ]);
    }

    public function recap(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->paginate(15)->withQueryString();

        $recapData = $this->calculateEmployeeRecap($employees->items(), $startDate, $endDate);

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();
        $positions = Position::where('company_id', $company->id)->select('id', 'name')->get();
        $setting = AttendanceSetting::where('company_id', $company->id)->first();

        $statusLabels = [
            'very_good' => $setting->status_very_good ?? 'Sangat Baik',
            'good' => $setting->status_good ?? 'Baik',
            'fair' => $setting->status_fair ?? 'Cukup',
            'poor' => $setting->status_poor ?? 'Kurang',
        ];

        return Inertia::render('Owner/Reports/Attendances/Recap', [
            'employees' => $employees,
            'recapData' => $recapData,
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusLabels' => $statusLabels,
            'filters' => $request->only(['branch_id', 'division_id', 'position_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function recapExport(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->get();

        $recapList = $this->calculateEmployeeRecap($employees, $startDate, $endDate);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Rekap-Kehadiran-' . $startDate . '-sd-' . $endDate . '.csv"',
        ];

        $callback = function () use ($recapList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NAMA KARYAWAN', 'NIP', 'CABANG', 'DIVISI', 'JADWAL KERJA', 'HADIR', 'TERLAMBAT', 'PULANG CEPAT', 'ALPHA', 'CUTI', 'TOTAL LEMBUR', 'EVALUASI']);

            foreach ($recapList as $r) {
                fputcsv($file, [
                    $r['employee']['name'],
                    $r['employee']['nip'] ?? '-',
                    $r['employee']['branch']['name'] ?? '-',
                    $r['employee']['division']['name'] ?? '-',
                    $r['total_schedule'],
                    $r['total_present'],
                    $r['total_late'],
                    $r['total_early_leaving'],
                    $r['total_alpha'],
                    $r['total_leave'],
                    $r['overtime_duration_formatted'],
                    $r['evaluation_status'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function rate(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->paginate(15)->withQueryString();

        $recapList = $this->calculateEmployeeRecap($employees->items(), $startDate, $endDate);

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        return Inertia::render('Owner/Reports/Attendances/Rate', [
            'employees' => $employees,
            'rateData' => $recapList,
            'branches' => $branches,
            'divisions' => $divisions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['branch_id', 'division_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function rateExport(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->get();

        $recapList = $this->calculateEmployeeRecap($employees, $startDate, $endDate);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Tingkat-Kehadiran-' . $startDate . '-sd-' . $endDate . '.csv"',
        ];

        $callback = function () use ($recapList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NAMA KARYAWAN', 'NIP', 'CABANG', 'DIVISI', 'HARI KERJA', 'HADIR', 'RATE KEHADIRAN (%)', 'EVALUASI']);

            foreach ($recapList as $r) {
                fputcsv($file, [
                    $r['employee']['name'],
                    $r['employee']['nip'] ?? '-',
                    $r['employee']['branch']['name'] ?? '-',
                    $r['employee']['division']['name'] ?? '-',
                    $r['total_schedule'],
                    $r['total_present'],
                    $r['attendance_rate'] . '%',
                    $r['evaluation_status'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function overtimeRecap(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = Employee::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->where('is_active', true);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $employees = $query->paginate(15)->withQueryString();
        $employeeIds = collect($employees->items())->pluck('id')->toArray();

        $overtimes = Overtime::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $recap = [];
        foreach ($employees->items() as $emp) {
            $empOvertimes = $overtimes->get($emp->id, collect());
            $totalCount = $empOvertimes->count();
            $totalMinutes = $empOvertimes->sum('duration_minutes');
            $hours = floor($totalMinutes / 60);
            $mins = $totalMinutes % 60;
            $durationFormatted = $totalMinutes > 0 ? "{$hours}j {$mins}m" : '0j';
            $estimatedPay = ($totalMinutes / 60) * ($emp->overtime_rate_per_hour ?? 0);

            $recap[$emp->id] = [
                'employee' => $emp,
                'total_count' => $totalCount,
                'total_minutes' => $totalMinutes,
                'duration_formatted' => $durationFormatted,
                'estimated_pay' => $estimatedPay,
            ];
        }

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        return Inertia::render('Owner/Reports/Attendances/OvertimeRecap', [
            'employees' => $employees,
            'overtimeData' => $recap,
            'branches' => $branches,
            'divisions' => $divisions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['branch_id', 'division_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function overtimeRecapExport(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = Employee::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->where('is_active', true);

        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('division_id')) $query->where('division_id', $request->division_id);

        $employees = $query->get();
        $employeeIds = $employees->pluck('id')->toArray();

        $overtimes = Overtime::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Rekap-Lembur-' . $startDate . '-sd-' . $endDate . '.csv"',
        ];

        $callback = function () use ($employees, $overtimes) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NAMA KARYAWAN', 'NIP', 'CABANG', 'DIVISI', 'JUMLAH LEMBUR', 'TOTAL MENIT', 'DURASI', 'ESTIMASI UPAH LEMBUR']);

            foreach ($employees as $emp) {
                $empOvertimes = $overtimes->get($emp->id, collect());
                $totalMinutes = $empOvertimes->sum('duration_minutes');
                $hours = floor($totalMinutes / 60);
                $mins = $totalMinutes % 60;
                $duration = $totalMinutes > 0 ? "{$hours}j {$mins}m" : '0j';
                $pay = ($totalMinutes / 60) * ($emp->overtime_rate_per_hour ?? 0);

                fputcsv($file, [
                    $emp->name,
                    $emp->nip ?? '-',
                    $emp->branch->name ?? '-',
                    $emp->division->name ?? '-',
                    $empOvertimes->count(),
                    $totalMinutes,
                    $duration,
                    $pay,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function overtimeRecapDetail(Request $request, $employeeId)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $employee = Employee::with(['branch:id,name', 'division:id,name', 'position:id,name'])
            ->where('company_id', $company->id)
            ->findOrFail($employeeId);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $overtimes = Overtime::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Owner/Reports/Attendances/OvertimeDetail', [
            'employee' => $employee,
            'overtimes' => $overtimes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }

    public function overtimeRecapDetailExport(Request $request, $employeeId)
    {
        $company = $request->user()->company;
        if (!$company) abort(403);

        $employee = Employee::where('company_id', $company->id)->findOrFail($employeeId);
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $overtimes = Overtime::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->latest('date')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Detail-Lembur-' . $employee->name . '-' . $startDate . '.csv"',
        ];

        $callback = function () use ($overtimes, $employee) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['TANGGAL', 'JAM MULAI', 'JAM SELESAI', 'DURASI (MENIT)', 'ALASAN / TUGAS']);

            foreach ($overtimes as $ov) {
                fputcsv($file, [
                    $ov->date,
                    $ov->start_time,
                    $ov->end_time,
                    $ov->duration_minutes,
                    $ov->reason,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getRecapQuery($company, Request $request)
    {
        $query = Employee::with(['branch:id,name', 'division:id,name', 'position:id,name'])
            ->where('company_id', $company->id)
            ->where('is_active', true);

        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('division_id')) $query->where('division_id', $request->division_id);
        if ($request->filled('position_id')) $query->where('position_id', $request->position_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function calculateEmployeeRecap($employees, $startDate, $endDate)
    {
        $employeeIds = collect($employees)->pluck('id')->toArray();

        $workSchedules = WorkSchedule::whereIn('employee_id', $employeeIds)
            ->where('is_day_off', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $attendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $leaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('employee_id');

        $overtimes = Overtime::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $recapList = [];

        foreach ($employees as $emp) {
            $empSchedules = $workSchedules->get($emp->id, collect());
            $empAttendances = $attendances->get($emp->id, collect());
            $empLeaves = $leaves->get($emp->id, collect());
            $empOvertimes = $overtimes->get($emp->id, collect());

            $totalSchedule = $empSchedules->count();
            if ($totalSchedule === 0) {
                $weekdays = 0;
                $cur = $start->copy();
                while ($cur->lte($end)) {
                    if (!$cur->isWeekend()) $weekdays++;
                    $cur->addDay();
                }
                $totalSchedule = max(1, $weekdays);
            }

            $presentRecords = $empAttendances->filter(function ($a) {
                return !in_array($a->attendance_status, ['alpha', 'absent', 'leave']) && !empty($a->clock_in_time);
            });
            $totalPresent = $presentRecords->count();

            $totalLeave = $empLeaves->count();
            $totalLate = $empAttendances->where('late_minutes', '>', 0)->count();
            $totalEarlyLeaving = $empAttendances->where('early_leaving_minutes', '>', 0)->count();
            $totalAlpha = $empAttendances->whereIn('attendance_status', ['alpha', 'absent'])->count();

            $totalOvertimeCount = $empOvertimes->count();
            $totalOvertimeMinutes = $empOvertimes->sum('duration_minutes');
            $hours = floor($totalOvertimeMinutes / 60);
            $mins = $totalOvertimeMinutes % 60;
            $overtimeDurationFormatted = $totalOvertimeMinutes > 0 ? "{$hours}j {$mins}m" : '0j';

            $rate = round(($totalPresent / max(1, $totalSchedule)) * 100);

            $evaluation = 'Kurang';
            if ($rate >= 95) $evaluation = 'Sangat Baik';
            elseif ($rate >= 85) $evaluation = 'Baik';
            elseif ($rate >= 75) $evaluation = 'Cukup';

            $recapList[$emp->id] = [
                'employee' => $emp,
                'total_schedule' => $totalSchedule,
                'total_present' => $totalPresent,
                'total_leave' => $totalLeave,
                'total_late' => $totalLate,
                'total_early_leaving' => $totalEarlyLeaving,
                'total_alpha' => $totalAlpha,
                'total_overtime_count' => $totalOvertimeCount,
                'total_overtime_minutes' => $totalOvertimeMinutes,
                'overtime_duration_formatted' => $overtimeDurationFormatted,
                'attendance_rate' => $rate,
                'evaluation_status' => $evaluation,
            ];
        }

        return $recapList;
    }
}
