<?php

namespace App\Http\Controllers\Web\Owner\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\Overtime;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\DailyReport;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function daily(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();
        $employees = Employee::where('company_id', $company->id)->where('is_active', true)->select('id', 'name', 'nip')->orderBy('name')->get();

        $query = $this->getDailyReportQuery($company, $request, $startDate, $endDate);

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $reports = $query->paginate($perPage)->withQueryString();

        // Summary statistics across filtered period
        $allReports = (clone $query)->get();
        $totalReports = $allReports->count();
        $reportingEmployeesCount = $allReports->pluck('user_id')->unique()->count();
        $totalAttachmentsCount = $allReports->sum(fn($r) => $r->attachments ? $r->attachments->count() : 0);

        return Inertia::render('Owner/Reports/Performances/Daily/Index', [
            'reports' => $reports,
            'totalReports' => $totalReports,
            'reportingEmployeesCount' => $reportingEmployeesCount,
            'totalAttachmentsCount' => $totalAttachmentsCount,
            'branches' => $branches,
            'divisions' => $divisions,
            'employees' => $employees,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $request->input('branch_id', ''),
                'division_id' => $request->input('division_id', ''),
                'employee_id' => $request->input('employee_id', ''),
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function dailyExport(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = $this->getDailyReportQuery($company, $request, $startDate, $endDate);
        $reports = $query->get();

        $filename = 'laporan-kerja-harian-' . $startDate . '-sd-' . $endDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Tanggal', 'Nama Karyawan', 'NIP', 'Cabang', 'Divisi', 'Judul Laporan', 'Keterangan', 'Jml Lampiran', 'Waktu Dibuat']);

            foreach ($reports as $r) {
                $emp = $r->user?->employee;
                fputcsv($file, [
                    Carbon::parse($r->date)->format('d/m/Y'),
                    $emp->name ?? $r->user?->name ?? '-',
                    $emp->nip ?? $emp->nik ?? '-',
                    $emp->branch->name ?? '-',
                    $emp->division->name ?? '-',
                    $r->title,
                    $r->description,
                    $r->attachments ? $r->attachments->count() : 0,
                    $r->created_at ? $r->created_at->format('d/m/Y H:i') : '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dailyShow($id)
    {
        $company = auth()->user()?->company;
        if (!$company) abort(403);

        $report = DailyReport::with(['user.employee.branch', 'user.employee.division', 'user.employee.position', 'attachments'])
            ->whereHas('user.employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        return Inertia::render('Owner/Reports/Performances/Daily/Show', [
            'report' => $report,
        ]);
    }

    public function employee(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();
        $positions = Position::where('company_id', $company->id)->select('id', 'name')->get();

        $data = $this->calculatePerformanceData($company, $request, $startDate, $endDate, true);

        return Inertia::render('Owner/Reports/Performances/Employee/Index', [
            'recapList' => $data['recapList'],
            'paginatedEmployees' => $data['paginatedEmployees'],
            'totalEmployees' => $data['totalEmployees'],
            'totalNetHours' => $data['totalNetHours'],
            'totalOvertimeHours' => $data['totalOvertimeHours'],
            'avgScore' => $data['avgScore'],
            'chartBarData' => $data['chartBarData'],
            'chartDonutData' => $data['chartDonutData'],
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $request->input('branch_id', ''),
                'division_id' => $request->input('division_id', ''),
                'position_id' => $request->input('position_id', ''),
                'search' => $request->input('search', ''),
                'per_page' => (int) $request->input('per_page', 10),
            ],
        ]);
    }

    public function employeeExport(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->calculatePerformanceData($company, $request, $startDate, $endDate, false);

        $filename = 'laporan-performa-karyawan-' . $startDate . '-sd-' . $endDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Nama Karyawan', 'NIP', 'Email', 'Cabang', 'Divisi', 'Jabatan', 'Target Hari', 'Hari Hadir', 'Jml Terlambat', 'Jam Kerja Kotor', 'Jam Istirahat', 'Jam Kerja Bersih', 'Jam Lembur', 'Disiplin Istirahat', 'Skor Performa']);

            foreach ($data['recapList'] as $row) {
                fputcsv($file, [
                    $row['name'],
                    $row['nip'],
                    $row['email'],
                    $row['branch'],
                    $row['division'],
                    $row['position'],
                    $row['scheduled_days'],
                    $row['present_days'],
                    $row['late_count'],
                    $row['gross_hours_text'],
                    $row['break_hours_text'],
                    $row['net_hours_text'],
                    $row['overtime_hours_text'],
                    $row['break_discipline_label'],
                    $row['score'] . '%',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function branch(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = range(date('Y') - 3, date('Y') + 1);

        $data = $this->calculateBranchPerformanceData($company, $request, $month, $year, true);

        return Inertia::render('Owner/Reports/Performances/Branch/Index', [
            'branchList' => $data['branchList'],
            'paginatedBranches' => $data['paginatedBranches'],
            'totalBranches' => $data['totalBranches'],
            'totalEmployeesAllBranches' => $data['totalEmployeesAllBranches'],
            'avgCompanyAttendance' => $data['avgCompanyAttendance'],
            'avgCompanyScore' => $data['avgCompanyScore'],
            'month' => $month,
            'year' => $year,
            'months' => $months,
            'years' => $years,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'search' => $request->input('search', ''),
                'per_page' => (int) $request->input('per_page', 10),
            ],
        ]);
    }

    public function branchExport(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403);

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        $data = $this->calculateBranchPerformanceData($company, $request, $month, $year, false);

        $filename = 'laporan-performa-cabang-' . $month . '-' . $year . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Nama Cabang', 'Kode Cabang', 'Jumlah Karyawan', 'Tingkat Kehadiran (%)', 'Rata-rata Jam Kerja', 'Karyawan Resign', 'Tingkat Turnover (%)', 'Skor Performa (%)', 'Status']);

            foreach ($data['branchList'] as $row) {
                fputcsv($file, [
                    $row['branch_name'],
                    $row['branch_code'],
                    $row['employee_count'],
                    $row['attendance_rate'] . '%',
                    $row['avg_work_hours'] . ' Jam',
                    $row['resigned_count'],
                    $row['turnover_rate'] . '%',
                    $row['performance_score'] . '%',
                    $row['status_label'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getDailyReportQuery($company, Request $request, $startDate, $endDate)
    {
        $query = DailyReport::with(['user.employee.branch', 'user.employee.division', 'user.employee.position', 'attachments'])
            ->whereHas('user.employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $startDate);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('user.employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('user.employee', function ($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('user.employee', function ($q) use ($request) {
                $q->where('id', $request->employee_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user.employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%")
                         ->orWhere('nik', 'like', "%{$search}%")
                         ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }

    private function calculateBranchPerformanceData($company, Request $request, $month, $year, $paginate = true)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $weekdaysCount = 0;
        $tempDay = $start->copy();
        while ($tempDay->lte($end)) {
            if (!$tempDay->isWeekend()) {
                $weekdaysCount++;
            }
            $tempDay->addDay();
        }

        $branchQuery = Branch::where('company_id', $company->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $branchQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $allEmployees = Employee::where('company_id', $company->id)->get();
        $empIds = $allEmployees->pluck('id')->toArray();

        $allWorkSchedules = WorkSchedule::whereIn('employee_id', $empIds)
            ->where('is_day_off', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $allAttendances = Attendance::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $allBranches = (clone $branchQuery)->get();
        $branchDataMap = [];

        $totalActiveEmployeesCompany = 0;
        $sumAttendanceRates = 0;
        $sumScores = 0;
        $branchesWithEmployeesCount = 0;

        foreach ($allBranches as $branch) {
            $activeEmps = $allEmployees->where('branch_id', $branch->id)->where('is_active', true);
            $empCount = $activeEmps->count();

            $resignedCount = $allEmployees->where('branch_id', $branch->id)->where('is_active', false)->count();

            $headcount = $empCount + $resignedCount;
            $turnoverRate = $headcount > 0 ? round(($resignedCount / $headcount) * 100, 1) : 0.0;

            $totalScheduledDays = 0;
            $totalPresentDays = 0;
            $totalWorkMinutes = 0;

            foreach ($activeEmps as $emp) {
                $scheds = $allWorkSchedules->get($emp->id, collect());
                $sDays = $scheds->count() > 0 ? $scheds->count() : $weekdaysCount;
                $totalScheduledDays += $sDays;

                $atts = $allAttendances->get($emp->id, collect());
                $pAtts = $atts->filter(function ($a) {
                    return !in_array($a->attendance_status, ['alpha', 'absent']) && !empty($a->clock_in_time);
                });

                $totalPresentDays += $pAtts->count();
                $totalWorkMinutes += $pAtts->sum('total_work_minutes');
            }

            $attendanceRate = $totalScheduledDays > 0 ? min(100.0, round(($totalPresentDays / $totalScheduledDays) * 100, 1)) : 0.0;
            $avgWorkHours = $totalPresentDays > 0 ? round(($totalWorkMinutes / $totalPresentDays) / 60, 1) : 0.0;

            if ($empCount === 0) {
                $perfScore = 0.0;
            } else {
                $attendanceScore = $attendanceRate;
                $hoursScore = min(100.0, ($avgWorkHours / 8.0) * 100.0);
                $retentionScore = max(0.0, 100.0 - ($turnoverRate * 2.0));
                $perfScore = round(($attendanceScore * 0.50) + ($hoursScore * 0.30) + ($retentionScore * 0.20), 1);
                $perfScore = min(100.0, max(0.0, $perfScore));
            }

            if ($perfScore >= 80.0) {
                $statusLabel = 'Sangat Baik';
                $statusClass = 'bg-emerald-600 text-white';
            } elseif ($perfScore >= 65.0) {
                $statusLabel = 'Baik';
                $statusClass = 'bg-blue-600 text-white';
            } elseif ($perfScore >= 50.0) {
                $statusLabel = 'Cukup';
                $statusClass = 'bg-amber-600 text-white';
            } else {
                $statusLabel = 'Perlu Perbaikan';
                $statusClass = 'bg-rose-600 text-white';
            }

            $totalActiveEmployeesCompany += $empCount;
            if ($empCount > 0) {
                $sumAttendanceRates += $attendanceRate;
                $sumScores += $perfScore;
                $branchesWithEmployeesCount++;
            }

            $branchDataMap[$branch->id] = [
                'id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'employee_count' => $empCount,
                'attendance_rate' => $attendanceRate,
                'avg_work_hours' => $avgWorkHours,
                'resigned_count' => $resignedCount,
                'turnover_rate' => $turnoverRate,
                'performance_score' => $perfScore,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
            ];
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        if ($paginate) {
            $paginatedBranches = $branchQuery->paginate($perPage)->withQueryString();
            $branchList = [];
            foreach ($paginatedBranches as $b) {
                if (isset($branchDataMap[$b->id])) {
                    $branchList[] = $branchDataMap[$b->id];
                }
            }
        } else {
            $paginatedBranches = null;
            $branchList = array_values($branchDataMap);
        }

        $totalBranchesCount = count($allBranches);
        $avgCompanyAttendance = $branchesWithEmployeesCount > 0 ? round($sumAttendanceRates / $branchesWithEmployeesCount, 1) : 0.0;
        $avgCompanyScore = $branchesWithEmployeesCount > 0 ? round($sumScores / $branchesWithEmployeesCount, 1) : 0.0;

        return [
            'branchList' => $branchList,
            'paginatedBranches' => $paginatedBranches,
            'totalBranches' => $totalBranchesCount,
            'totalEmployeesAllBranches' => $totalActiveEmployeesCompany,
            'avgCompanyAttendance' => $avgCompanyAttendance,
            'avgCompanyScore' => $avgCompanyScore,
        ];
    }

    private function calculatePerformanceData($company, Request $request, $startDate, $endDate, $paginate = true)
    {
        $empQuery = Employee::with(['user:id,email', 'branch:id,name', 'division:id,name', 'position:id,name'])
            ->where('company_id', $company->id)
            ->where('is_active', true);

        if ($request->filled('branch_id')) {
            $empQuery->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $empQuery->where('division_id', $request->division_id);
        }

        if ($request->filled('position_id')) {
            $empQuery->where('position_id', $request->position_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $empQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        $allFilteredEmployees = (clone $empQuery)->get();
        $employeeIds = $allFilteredEmployees->pluck('id')->toArray();

        $attendances = Attendance::with('shift:id,clock_in,clock_out')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $workSchedules = WorkSchedule::whereIn('employee_id', $employeeIds)
            ->where('is_day_off', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $overtimes = Overtime::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $periodWeekdays = 0;
        $tempDay = $start->copy();
        while ($tempDay->lte($end)) {
            if (!$tempDay->isWeekend()) {
                $periodWeekdays++;
            }
            $tempDay->addDay();
        }

        $allProcessed = [];
        $totalAllNetMinutes = 0;
        $totalAllOtMinutes = 0;
        $totalScoreSum = 0;

        $donutCounts = [
            'obedient' => 0,
            'fair' => 0,
            'warning' => 0,
            'none' => 0,
        ];

        foreach ($allFilteredEmployees as $emp) {
            $empAtt = $attendances->get($emp->id, collect());
            $empSched = $workSchedules->get($emp->id, collect());
            $empOt = $overtimes->get($emp->id, collect());

            $scheduledDays = $empSched->count() > 0 ? $empSched->count() : $periodWeekdays;

            $presentRecords = $empAtt->filter(function ($a) {
                return !in_array($a->attendance_status, ['alpha', 'absent']) && !empty($a->clock_in_time);
            });
            $presentDays = $presentRecords->count();
            $lateCount = $presentRecords->where('late_minutes', '>', 0)->count();

            $grossMinutes = 0;
            $breakMinutes = 0;
            $hasBreakRule = false;
            $earlyLeavingViolations = 0;

            foreach ($presentRecords as $att) {
                $wMin = $att->total_work_minutes;
                if ($wMin <= 0 && $att->clock_in_time && $att->clock_out_time) {
                    $in = Carbon::parse($att->clock_in_time);
                    $out = Carbon::parse($att->clock_out_time);
                    $wMin = $in->diffInMinutes($out);
                }
                $grossMinutes += max(0, $wMin);

                if ($att->shift) {
                    $sIn = Carbon::parse($att->shift->clock_in);
                    $sOut = Carbon::parse($att->shift->clock_out);
                    $shiftHours = $sIn->diffInMinutes($sOut) / 60;
                    if ($shiftHours >= 8) {
                        $breakMinutes += 60;
                        $hasBreakRule = true;
                    }
                }

                if ($att->early_leaving_minutes > 0) {
                    $earlyLeavingViolations += $att->early_leaving_minutes;
                }
            }

            $netMinutes = max(0, $grossMinutes - $breakMinutes);
            $targetMinutes = max(1, $scheduledDays * 8 * 60);
            $otMinutes = $empOt->sum('duration_minutes');

            if (!$hasBreakRule) {
                $disciplineKey = 'none';
                $disciplineLabel = 'Tidak ada aturan';
                $disciplineClass = 'bg-slate-100 text-slate-700 border-slate-200';
            } else {
                if ($earlyLeavingViolations == 0) {
                    $disciplineKey = 'obedient';
                    $disciplineLabel = 'Patuh';
                    $disciplineClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                } elseif ($earlyLeavingViolations <= 30) {
                    $disciplineKey = 'fair';
                    $disciplineLabel = 'Cukup';
                    $disciplineClass = 'bg-amber-50 text-amber-700 border-amber-200';
                } else {
                    $disciplineKey = 'warning';
                    $disciplineLabel = 'Perlu perhatian';
                    $disciplineClass = 'bg-rose-50 text-rose-700 border-rose-200';
                }
            }
            $donutCounts[$disciplineKey]++;

            if ($scheduledDays <= 0 || $presentDays <= 0) {
                $score = 0;
            } else {
                $attendanceRatio = min(1.0, $presentDays / max(1, $scheduledDays));
                $punctualityRatio = max(0, 1.0 - ($lateCount / max(1, $presentDays)));
                $hoursRatio = min(1.0, $netMinutes / $targetMinutes);
                $score = round(($attendanceRatio * 45) + ($punctualityRatio * 35) + ($hoursRatio * 20), 1);
            }

            $totalAllNetMinutes += $netMinutes;
            $totalAllOtMinutes += $otMinutes;
            $totalScoreSum += $score;

            $allProcessed[$emp->id] = [
                'id' => $emp->id,
                'name' => $emp->name,
                'nip' => $emp->nip ?? $emp->nik ?? '-',
                'email' => $emp->user->email ?? $emp->email ?? '-',
                'branch' => $emp->branch->name ?? '-',
                'division' => $emp->division->name ?? '-',
                'position' => $emp->position->name ?? '-',
                'scheduled_days' => $scheduledDays,
                'present_days' => $presentDays,
                'late_count' => $lateCount,
                'gross_minutes' => $grossMinutes,
                'gross_hours_text' => $this->formatHoursMinutes($grossMinutes),
                'break_minutes' => $breakMinutes,
                'break_hours_text' => $hasBreakRule ? $this->formatHoursMinutes($breakMinutes) : 'Tidak ada',
                'net_minutes' => $netMinutes,
                'net_hours_text' => $this->formatHoursMinutes($netMinutes),
                'target_minutes' => $targetMinutes,
                'target_hours_text' => $this->formatHoursMinutes($targetMinutes),
                'overtime_minutes' => $otMinutes,
                'overtime_hours_text' => $this->formatHoursMinutes($otMinutes),
                'break_discipline_key' => $disciplineKey,
                'break_discipline_label' => $disciplineLabel,
                'break_discipline_class' => $disciplineClass,
                'score' => $score,
            ];
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        if ($paginate) {
            $paginatedEmployees = $empQuery->paginate($perPage)->withQueryString();
            $recapList = [];
            foreach ($paginatedEmployees as $emp) {
                if (isset($allProcessed[$emp->id])) {
                    $recapList[] = $allProcessed[$emp->id];
                }
            }
        } else {
            $paginatedEmployees = null;
            $recapList = array_values($allProcessed);
        }

        $totalEmployeesCount = count($allProcessed);
        $avgScore = $totalEmployeesCount > 0 ? round($totalScoreSum / $totalEmployeesCount, 1) : 0;

        $chartEmployees = array_slice($allProcessed, 0, 10);
        $barLabels = [];
        $barNetHours = [];
        $barOtHours = [];

        foreach ($chartEmployees as $row) {
            $barLabels[] = $row['name'];
            $barNetHours[] = round($row['net_minutes'] / 60, 1);
            $barOtHours[] = round($row['overtime_minutes'] / 60, 1);
        }

        $chartBarData = [
            'labels' => $barLabels,
            'net_hours' => $barNetHours,
            'overtime_hours' => $barOtHours,
        ];

        $chartDonutData = [
            'labels' => ['Patuh', 'Cukup', 'Perlu perhatian', 'Tidak ada aturan'],
            'data' => [
                $donutCounts['obedient'],
                $donutCounts['fair'],
                $donutCounts['warning'],
                $donutCounts['none'],
            ],
            'colors' => ['#10B981', '#F59E0B', '#F43F5E', '#94A3B8'],
        ];

        return [
            'recapList' => $recapList,
            'paginatedEmployees' => $paginatedEmployees,
            'totalEmployees' => $totalEmployeesCount,
            'totalNetHours' => $this->formatHoursMinutes($totalAllNetMinutes),
            'totalOvertimeHours' => $this->formatHoursMinutes($totalAllOtMinutes),
            'avgScore' => $avgScore,
            'chartBarData' => $chartBarData,
            'chartDonutData' => $chartDonutData,
        ];
    }

    private function formatHoursMinutes($minutes)
    {
        if (!$minutes || $minutes <= 0) return '0 Menit';
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return "{$h} Jam {$m} Menit";
        if ($h > 0) return "{$h} Jam";
        return "{$m} Menit";
    }
}
