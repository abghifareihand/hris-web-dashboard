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

        $query = Attendance::with([
            'employee:id,name,nip,branch_id,division_id,user_id',
            'employee.user:id,avatar',
            'employee.division:id,name',
            'employee.branch:id,name',
            'shift:id,name,clock_in,clock_out',
        ])->where('company_id', $company->id);

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

        $defaultToday = now()->format('Y-m-d');
        $hasAllDates = $request->boolean('all_dates') || $request->input('period') === 'all';
        $hasExplicitDate = $request->filled('start_date') || $request->filled('end_date');
        $hasAnyDateQuery = $request->query->has('start_date') || $request->query->has('end_date');

        if ($hasAllDates) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } elseif ($hasExplicitDate) {
            $isInitialLoad = false;
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } elseif ($hasAnyDateQuery) {
            // User had query parameters like ?start_date=&end_date= (empty)
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } else {
            // Initial load without any date parameters
            $isInitialLoad = true;
            $startDate = $defaultToday;
            $endDate = $defaultToday;
        }

        if (!empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $attendances = $query->latest('date')->paginate($request->input('per_page', 10))->withQueryString();

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        $filters = array_merge(
            $request->only(['branch_id', 'division_id', 'status', 'search']),
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'all_dates' => ($hasAllDates || (!$isInitialLoad && empty($startDate) && empty($endDate))) ? 1 : 0,
            ]
        );

        $totalRecords = (clone $query)->count();
        $totalPresent = (clone $query)->whereIn('attendance_status', ['present', 'on_time', 'late', 'clocked_in'])->count();
        $totalLate = (clone $query)->where(function($q) {
            $q->where('attendance_status', 'late')->orWhere('late_minutes', '>', 0);
        })->count();

        $summary = [
            'totalRecords' => $totalRecords,
            'totalPresent' => $totalPresent,
            'totalLate' => $totalLate,
        ];

        return Inertia::render('Owner/Reports/Attendances/Index', [
            'attendances' => $attendances,
            'summary' => $summary,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => $filters,
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

        $defaultStartDate = now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');

        $hasAllDates = $request->boolean('all_dates') || $request->input('period') === 'all';
        $hasExplicitDate = $request->filled('start_date') || $request->filled('end_date');
        $hasAnyDateQuery = $request->query->has('start_date') || $request->query->has('end_date');

        if ($hasAllDates) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } elseif ($hasExplicitDate) {
            $isInitialLoad = false;
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } elseif ($hasAnyDateQuery) {
            // User explicitly sent empty date range (?start_date=&end_date=)
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } else {
            // Initial load without any date parameters
            $isInitialLoad = true;
            $startDate = $defaultStartDate;
            $endDate = $defaultEndDate;
        }

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->paginate($request->input('per_page', 10))->withQueryString();

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

        return Inertia::render('Owner/Reports/Attendances/Recap/Index', [
            'employees' => $employees,
            'recapData' => $recapData,
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusLabels' => $statusLabels,
            'filters' => array_merge(
                $request->only(['branch_id', 'division_id', 'position_id', 'search']),
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'all_dates' => ($hasAllDates || (!$isInitialLoad && empty($startDate) && empty($endDate))) ? 1 : 0,
                ]
            ),
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

        $defaultStartDate = now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');

        $hasAllDates = $request->boolean('all_dates') || $request->input('period') === 'all';
        $hasExplicitDate = $request->filled('start_date') || $request->filled('end_date');
        $hasAnyDateQuery = $request->query->has('start_date') || $request->query->has('end_date');

        if ($hasAllDates) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } elseif ($hasExplicitDate) {
            $isInitialLoad = false;
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } elseif ($hasAnyDateQuery) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } else {
            $isInitialLoad = true;
            $startDate = $defaultStartDate;
            $endDate = $defaultEndDate;
        }

        $query = $this->getRecapQuery($company, $request);
        $employees = $query->paginate($request->input('per_page', 10))->withQueryString();

        $recapList = $this->calculateEmployeeRecap($employees->items(), $startDate, $endDate);

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();
        $setting = AttendanceSetting::where('company_id', $company->id)->first();

        $statusLabels = [
            'very_good' => $setting->status_very_good ?? 'Sangat Baik',
            'good' => $setting->status_good ?? 'Baik',
            'fair' => $setting->status_fair ?? 'Cukup',
            'poor' => $setting->status_poor ?? 'Kurang',
        ];

        // Calculate all active employees recap for global metrics & per-branch statistics
        $allActiveEmployees = Employee::with(['branch:id,name', 'division:id,name', 'position:id,name', 'user:id,avatar'])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $allRecapList = $this->calculateEmployeeRecap($allActiveEmployees, $startDate, $endDate);

        $branchRates = $branches->map(function ($branch) use ($allActiveEmployees, $allRecapList, $statusLabels) {
            $branchEmployees = $allActiveEmployees->where('branch_id', $branch->id);
            $count = $branchEmployees->count();

            if ($count === 0) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'employee_count' => 0,
                    'attendance_rate' => 0,
                    'status' => $statusLabels['poor'],
                ];
            }

            $rates = $branchEmployees->map(function ($emp) use ($allRecapList) {
                return $allRecapList[$emp->id]['attendance_rate'] ?? 0;
            });

            $avgRate = round($rates->avg(), 1);

            $status = $statusLabels['poor'];
            if ($avgRate >= 95) $status = $statusLabels['very_good'];
            elseif ($avgRate >= 85) $status = $statusLabels['good'];
            elseif ($avgRate >= 75) $status = $statusLabels['fair'];

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'employee_count' => $count,
                'attendance_rate' => $avgRate,
                'status' => $status,
            ];
        });

        $globalAvgRate = count($allRecapList) > 0 ? round(collect($allRecapList)->avg('attendance_rate'), 1) : 0;
        $globalWorkingDays = count($allRecapList) > 0 ? collect($allRecapList)->max('total_schedule') : 0;
        $globalTotalEmployees = $allActiveEmployees->count();

        $summary = [
            'avgRate' => $globalAvgRate,
            'totalWorkingDays' => $globalWorkingDays,
            'totalCount' => $globalTotalEmployees,
        ];

        return Inertia::render('Owner/Reports/Attendances/Rate/Index', [
            'employees' => $employees,
            'rateData' => $recapList,
            'branchRates' => $branchRates,
            'summary' => $summary,
            'branches' => $branches,
            'divisions' => $divisions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusLabels' => $statusLabels,
            'filters' => array_merge(
                $request->only(['branch_id', 'division_id', 'search']),
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'all_dates' => ($hasAllDates || (!$isInitialLoad && empty($startDate) && empty($endDate))) ? 1 : 0,
                ]
            ),
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

        $defaultStartDate = now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');

        $hasAllDates = $request->boolean('all_dates') || $request->input('period') === 'all';
        $hasExplicitDate = $request->filled('start_date') || $request->filled('end_date');
        $hasAnyDateQuery = $request->query->has('start_date') || $request->query->has('end_date');

        if ($hasAllDates) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } elseif ($hasExplicitDate) {
            $isInitialLoad = false;
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } elseif ($hasAnyDateQuery) {
            $isInitialLoad = false;
            $startDate = null;
            $endDate = null;
        } else {
            $isInitialLoad = true;
            $startDate = $defaultStartDate;
            $endDate = $defaultEndDate;
        }

        $status = $request->input('status', 'approved');

        $query = Employee::with(['branch:id,name', 'division:id,name', 'position:id,name', 'user:id,avatar'])
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

        // Hanya tampilkan karyawan yang benar-benar memiliki data lembur pada periode & status yang dipilih
        $query->whereHas('overtimes', function ($q) use ($startDate, $endDate, $status) {
            if ($status && $status !== 'all') {
                $q->where('status', $status);
            }
            if (!empty($startDate) && !empty($endDate)) {
                $q->whereBetween('date', [$startDate, $endDate]);
            } elseif (!empty($startDate)) {
                $q->where('date', '>=', $startDate);
            } elseif (!empty($endDate)) {
                $q->where('date', '<=', $endDate);
            }
        });

        $query->orderBy('name', 'asc');

        $employees = $query->paginate($request->input('per_page', 10))->withQueryString();
        $employeeIds = collect($employees->items())->pluck('id')->toArray();

        $overtimesQuery = Overtime::whereIn('employee_id', $employeeIds);
        if ($status && $status !== 'all') {
            $overtimesQuery->where('status', $status);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $overtimesQuery->whereBetween('date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $overtimesQuery->where('date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $overtimesQuery->where('date', '<=', $endDate);
        }

        $overtimes = $overtimesQuery->get()->groupBy('employee_id');

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

        // Global summary calculation for all filtered data
        $allOvertimeQuery = Overtime::whereHas('employee', function($q) use ($company, $request) {
            $q->where('company_id', $company->id)->where('is_active', true);
            if ($request->filled('branch_id')) $q->where('branch_id', $request->branch_id);
            if ($request->filled('division_id')) $q->where('division_id', $request->division_id);
            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%");
                });
            }
        });

        if ($status && $status !== 'all') {
            $allOvertimeQuery->where('status', $status);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $allOvertimeQuery->whereBetween('date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $allOvertimeQuery->where('date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $allOvertimeQuery->where('date', '<=', $endDate);
        }

        $allApprovedOvertimes = $allOvertimeQuery->get(['employee_id', 'duration_minutes']);
        $totalEmployeesWithOt = $allApprovedOvertimes->pluck('employee_id')->unique()->count();
        $totalRequests = $allApprovedOvertimes->count();
        $totalMinutes = $allApprovedOvertimes->sum('duration_minutes');
        $hours = floor($totalMinutes / 60);
        $mins = $totalMinutes % 60;
        $totalDurationFormatted = $totalMinutes > 0 ? "{$hours}j {$mins}m" : '0j';

        $summary = [
            'employeesWithOt' => $totalEmployeesWithOt,
            'totalRequests' => $totalRequests,
            'durationFormatted' => $totalDurationFormatted,
        ];

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        return Inertia::render('Owner/Reports/Attendances/Overtime/Index', [
            'employees' => $employees,
            'overtimeData' => $recap,
            'summary' => $summary,
            'branches' => $branches,
            'divisions' => $divisions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => array_merge(
                $request->only(['branch_id', 'division_id', 'search']),
                [
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'all_dates' => ($hasAllDates || (!$isInitialLoad && empty($startDate) && empty($endDate))) ? 1 : 0,
                ]
            ),
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
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Reports/Attendances/Overtime/Show', [
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
        $query = Employee::with(['branch:id,name', 'division:id,name', 'position:id,name', 'user:id,avatar'])
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

        $query->orderBy('name', 'asc');

        return $query;
    }

    private function calculateEmployeeRecap($employees, $startDate = null, $endDate = null)
    {
        $employeeIds = collect($employees)->pluck('id')->toArray();

        $workSchedulesQuery = WorkSchedule::whereIn('employee_id', $employeeIds)
            ->where('is_day_off', false);
        if (!empty($startDate) && !empty($endDate)) {
            $workSchedulesQuery->whereBetween('date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $workSchedulesQuery->where('date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $workSchedulesQuery->where('date', '<=', $endDate);
        }
        $workSchedules = $workSchedulesQuery->get()->groupBy('employee_id');

        $attendancesQuery = Attendance::whereIn('employee_id', $employeeIds);
        if (!empty($startDate) && !empty($endDate)) {
            $attendancesQuery->whereBetween('date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $attendancesQuery->where('date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $attendancesQuery->where('date', '<=', $endDate);
        }
        $attendances = $attendancesQuery->get()->groupBy('employee_id');

        $leavesQuery = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved');
        if (!empty($startDate) && !empty($endDate)) {
            $leavesQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            });
        }
        $leaves = $leavesQuery->get()->groupBy('employee_id');

        $overtimesQuery = Overtime::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved');
        if (!empty($startDate) && !empty($endDate)) {
            $overtimesQuery->whereBetween('date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $overtimesQuery->where('date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $overtimesQuery->where('date', '<=', $endDate);
        }
        $overtimes = $overtimesQuery->get()->groupBy('employee_id');

        $minDate = !empty($startDate) ? $startDate : (Attendance::whereIn('employee_id', $employeeIds)->min('date') ?? now()->startOfMonth()->format('Y-m-d'));
        $maxDate = !empty($endDate) ? $endDate : now()->format('Y-m-d');

        $start = Carbon::parse($minDate);
        $end = Carbon::parse($maxDate);

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

            $totalViolations = $totalLate + $totalEarlyLeaving;
            $rate = round(($totalPresent / max(1, $totalSchedule)) * 100);

            $setting = AttendanceSetting::where('company_id', $emp->company_id)->first();
            $labelVeryGood = $setting->status_very_good ?? 'Sangat Baik';
            $labelGood = $setting->status_good ?? 'Baik';
            $labelFair = $setting->status_fair ?? 'Cukup';
            $labelPoor = $setting->status_poor ?? 'Kurang';

            $evaluation = $labelPoor;
            if ($rate >= 95) $evaluation = $labelVeryGood;
            elseif ($rate >= 85) $evaluation = $labelGood;
            elseif ($rate >= 75) $evaluation = $labelFair;

            $recapList[$emp->id] = [
                'employee' => $emp,
                'total_schedule' => $totalSchedule,
                'total_present' => $totalPresent,
                'total_leave' => $totalLeave,
                'total_late' => $totalLate,
                'total_early_leaving' => $totalEarlyLeaving,
                'total_alpha' => $totalAlpha,
                'total_violations' => $totalViolations,
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
