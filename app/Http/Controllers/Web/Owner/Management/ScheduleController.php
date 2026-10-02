<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    // ==========================================
    // SHIFTS
    // ==========================================
    public function shifts(Request $request)
    {
        $company = auth()->user()->company;
        $query = Shift::where('company_id', $company->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $shifts = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Schedules/Shifts/Index', [
            'shifts' => $shifts,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function createShifts()
    {
        return Inertia::render('Owner/Management/Schedules/Shifts/Create');
    }

    public function storeShift(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
        ]);

        $company = auth()->user()->company;
        Shift::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        return redirect()->route('owner.management.schedules.shifts.index')
            ->with('success', 'Data shift berhasil ditambahkan.');
    }

    public function editShift($id)
    {
        $shift = Shift::where('company_id', auth()->user()->company->id)->findOrFail($id);
        return Inertia::render('Owner/Management/Schedules/Shifts/Edit', [
            'shift' => $shift,
        ]);
    }

    public function updateShift(Request $request, $id)
    {
        $shift = Shift::where('company_id', auth()->user()->company->id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
        ]);

        $shift->update([
            'name' => $request->name,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        return redirect()->route('owner.management.schedules.shifts.index')
            ->with('success', 'Data shift berhasil diperbarui.');
    }

    public function destroyShift($id)
    {
        $shift = Shift::where('company_id', auth()->user()->company->id)->findOrFail($id);
        $shift->delete();

        return redirect()->route('owner.management.schedules.shifts.index')
            ->with('success', 'Data shift berhasil dihapus.');
    }

    // ==========================================
    // HOLIDAYS
    // ==========================================
    public function holidays(Request $request)
    {
        $company = auth()->user()->company;
        $query = Holiday::with(['branch:id,name', 'division:id,name'])->where('company_id', $company->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('month')) {
            $query->where(function($q) use ($request) {
                $q->whereMonth('start_date', $request->month)
                  ->orWhereMonth('end_date', $request->month);
            });
        }

        if ($request->filled('year')) {
            $query->where(function($q) use ($request) {
                $q->whereYear('start_date', $request->year)
                  ->orWhereYear('end_date', $request->year);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $holidays = $query->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Schedules/Holidays/Index', [
            'holidays' => $holidays,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'search' => $request->search,
                'month' => $request->month,
                'year' => $request->year,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
            ],
        ]);
    }

    public function createHolidays()
    {
        $company = auth()->user()->company;
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Schedules/Holidays/Create', [
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'branch' => 'nullable|string',
            'division' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $company = auth()->user()->company;
        
        $branchId = ($request->branch && $request->branch !== 'all') ? $request->branch : null;
        $divisionId = ($request->division && $request->division !== 'all') ? $request->division : null;

        Holiday::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'name' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('owner.management.schedules.holidays.index')
            ->with('success', 'Data hari libur berhasil ditambahkan.');
    }

    public function editHoliday($id)
    {
        $company = auth()->user()->company;
        $holiday = Holiday::where('company_id', $company->id)->findOrFail($id);
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Schedules/Holidays/Edit', [
            'holiday' => $holiday,
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    public function updateHoliday(Request $request, $id)
    {
        $holiday = Holiday::where('company_id', auth()->user()->company->id)->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'branch' => 'nullable|string',
            'division' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $branchId = ($request->branch && $request->branch !== 'all') ? $request->branch : null;
        $divisionId = ($request->division && $request->division !== 'all') ? $request->division : null;

        $holiday->update([
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'name' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('owner.management.schedules.holidays.index')
            ->with('success', 'Data hari libur berhasil diperbarui.');
    }

    public function destroyHoliday($id)
    {
        $holiday = Holiday::where('company_id', auth()->user()->company->id)->findOrFail($id);
        $holiday->delete();

        return redirect()->route('owner.management.schedules.holidays.index')
            ->with('success', 'Data libur berhasil dihapus.');
    }

    // ==========================================
    // WORK SCHEDULES
    // ==========================================
    public function work(Request $request)
    {
        $company = auth()->user()->company;
        $shifts = Shift::where('company_id', $company->id)->orderBy('name')->get();
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $employees = Employee::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        $defaultMonth = (int) date('n');
        $defaultYear = (int) date('Y');
        $month = (int) $request->input('month', $defaultMonth);
        $year = (int) $request->input('year', $defaultYear);
        
        $schedulesQuery = WorkSchedule::with([
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
            'employee.user:id,name,email,avatar',
            'shift'
        ])
            ->whereHas('employee', function($q) use ($company, $request) {
                $q->where('company_id', $company->id);
                if ($request->filled('branch_id')) {
                    $q->where('branch_id', $request->branch_id);
                }
                if ($request->filled('division_id')) {
                    $q->where('division_id', $request->division_id);
                }
                if ($request->filled('search')) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('nip', 'like', '%' . $request->search . '%');
                }
                if ($request->filled('employee_id')) {
                    $q->where('id', $request->employee_id);
                }
            })
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        if ($request->filled('shift_id')) {
            if ($request->shift_id === 'day_off') {
                $schedulesQuery->where('is_day_off', true);
            } else {
                $schedulesQuery->where('shift_id', $request->shift_id);
            }
        }

        // Calendar schedules: all schedules in the selected month
        $calendarSchedules = (clone $schedulesQuery)->get()->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'date' => Carbon::parse($schedule->date)->format('Y-m-d'),
                'day' => (int) Carbon::parse($schedule->date)->format('j'),
                'name' => $schedule->employee->name ?? 'Unknown',
                'shift_name' => ($schedule->is_day_off || !$schedule->shift) ? 'Libur' : ($schedule->shift->name ?? '-'),
                'is_day_off' => (bool) $schedule->is_day_off,
                'division' => $schedule->employee->division->name ?? '-',
                'position' => $schedule->employee->position->name ?? '-',
            ];
        });

        // Table schedules: paginated (10 per page) with full details
        $schedules = (clone $schedulesQuery)
            ->orderBy('date', 'desc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString()
            ->through(function($schedule) use ($branches) {
                $allowedBranchesNames = 'Semua Cabang';
                if (!$schedule->is_day_off && $schedule->shift) {
                    if ($schedule->attendance_type === 'one' && !empty($schedule->allowed_branches)) {
                        $branchId = reset($schedule->allowed_branches);
                        $branch = $branches->firstWhere('id', $branchId);
                        $allowedBranchesNames = $branch ? $branch->name : '-';
                    } elseif ($schedule->attendance_type === 'some' && !empty($schedule->allowed_branches)) {
                        $allowedBranchesNames = $branches->whereIn('id', $schedule->allowed_branches)->pluck('name')->implode(', ');
                    }
                }

                return [
                    'id' => $schedule->id,
                    'date' => Carbon::parse($schedule->date)->format('Y-m-d'),
                    'day' => (int) Carbon::parse($schedule->date)->format('j'),
                    'employee_id' => $schedule->employee_id,
                    'employee' => [
                        'id' => $schedule->employee->id ?? null,
                        'name' => $schedule->employee->name ?? 'Unknown',
                        'nip' => $schedule->employee->nip ?? '-',
                        'email' => $schedule->employee->user->email ?? ($schedule->employee->email ?? '-'),
                        'user' => [
                            'avatar' => $schedule->employee->user->avatar ?? null,
                        ],
                        'division' => $schedule->employee->division ? ['name' => $schedule->employee->division->name] : null,
                        'position' => $schedule->employee->position ? ['name' => $schedule->employee->position->name] : null,
                        'branch' => $schedule->employee->branch ? ['name' => $schedule->employee->branch->name] : null,
                    ],
                    'shift_name' => ($schedule->is_day_off || !$schedule->shift) ? 'Libur' : ($schedule->shift->name ?? '-'),
                    'clock_in' => $schedule->shift ? Carbon::parse($schedule->shift->clock_in)->format('H:i') : null,
                    'clock_out' => $schedule->shift ? Carbon::parse($schedule->shift->clock_out)->format('H:i') : null,
                    'is_day_off' => (bool) $schedule->is_day_off,
                    'allowed_branches' => $allowedBranchesNames,
                ];
            });

        $holidayQuery = Holiday::where('company_id', $company->id)
            ->where(function($q) use ($month, $year) {
                $q->whereMonth('start_date', $month)->whereYear('start_date', $year)
                  ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
            });
            
        if ($request->filled('branch_id')) {
            $holidayQuery->where(function($q) use ($request) {
                $q->whereNull('branch_id')->orWhere('branch_id', $request->branch_id);
            });
        }
        
        if ($request->filled('division_id')) {
            $holidayQuery->where(function($q) use ($request) {
                $q->whereNull('division_id')->orWhere('division_id', $request->division_id);
            });
        }
        
        $holidays = $holidayQuery->get(['id', 'name', 'start_date', 'end_date']);

        return Inertia::render('Owner/Management/Schedules/Work/Index', [
            'shifts' => $shifts,
            'branches' => $branches,
            'divisions' => $divisions,
            'employees' => $employees,
            'schedules' => $schedules,
            'calendarSchedules' => $calendarSchedules,
            'holidays' => $holidays,
            'currentMonth' => $defaultMonth,
            'currentYear' => $defaultYear,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'employee_id' => $request->employee_id,
                'shift_id' => $request->shift_id,
                'search' => $request->search,
            ],
        ]);
    }

    public function createWork()
    {
        $company = auth()->user()->company;
        $shifts = Shift::where('company_id', $company->id)->orderBy('name')->get();
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $holidays = Holiday::where('company_id', $company->id)->get(['id', 'name', 'start_date', 'end_date']);
        
        return Inertia::render('Owner/Management/Schedules/Work/Create', [
            'shifts' => $shifts,
            'branches' => $branches,
            'divisions' => $divisions,
            'holidays' => $holidays,
        ]);
    }

    public function apiEmployees(Request $request)
    {
        $company = auth()->user()->company;
        $query = Employee::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $employees = $query->limit(100)->get(['id', 'name', 'branch_id', 'division_id']);

        return response()->json($employees->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'branch_id' => $e->branch_id,
                'division_id' => $e->division_id,
                'branch_name' => $e->branch ? $e->branch->name : '-',
                'division_name' => $e->division ? $e->division->name : '-'
            ];
        }));
    }

    public function storeWork(Request $request)
    {
        if (is_string($request->employees)) {
            $request->merge(['employees' => array_filter(explode(',', $request->employees))]);
        }
        if (is_string($request->months)) {
            $request->merge(['months' => array_filter(explode(',', $request->months))]);
        }

        $request->validate([
            'employees' => 'required|array',
            'employees.*' => 'exists:employees,id',
            'mode' => 'required|in:hari,tanggal',
            'months' => 'exclude_if:mode,tanggal|required|array',
            'months.*' => 'exclude_if:mode,tanggal|integer|between:1,12',
            'year' => 'exclude_if:mode,tanggal|required|integer',
            'shifts' => 'exclude_if:mode,tanggal|nullable|array',
            'date' => 'exclude_if:mode,hari|required|date',
            'shift_date' => 'exclude_if:mode,hari|nullable',
        ]);

        $company = auth()->user()->company;
        $employees = $request->employees;

        DB::transaction(function () use ($request, $company, $employees) {
            if ($request->mode === 'hari') {
                $dayNames = [
                    0 => 'minggu',
                    1 => 'senin',
                    2 => 'selasa',
                    3 => 'rabu',
                    4 => 'kamis',
                    5 => 'jumat',
                    6 => 'sabtu'
                ];

                foreach ($employees as $employeeId) {
                    foreach ($request->months as $month) {
                        $daysInMonth = Carbon::create($request->year, $month, 1)->daysInMonth;
                        
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = Carbon::create($request->year, $month, $day);
                            $dayOfWeekName = $dayNames[$date->dayOfWeek];
                            
                            $shiftId = $request->shifts[$dayOfWeekName] ?? null;
                            $isDayOff = empty($shiftId);

                            $attendanceType = 'all';
                            $allowedBranches = null;

                            if (!$isDayOff) {
                                $attendanceType = $request->input("area.{$dayOfWeekName}", 'all');
                                if ($attendanceType === 'one') {
                                    $allowedBranches = array_filter([$request->input("branch_one.{$dayOfWeekName}")]);
                                } elseif ($attendanceType === 'some') {
                                    $allowedBranches = array_filter($request->input("branch_some.{$dayOfWeekName}", []));
                                }
                            }

                            WorkSchedule::updateOrCreate(
                                [
                                    'employee_id' => $employeeId,
                                    'date' => $date->toDateString(),
                                ],
                                [
                                    'company_id' => $company->id,
                                    'shift_id' => $shiftId ?: null,
                                    'is_day_off' => $isDayOff,
                                    'attendance_type' => $attendanceType,
                                    'allowed_branches' => $allowedBranches,
                                ]
                            );
                        }
                    }
                }
            } else {
                $date = Carbon::parse($request->date);
                $shiftId = $request->shift_date ?? null;
                $isDayOff = empty($shiftId);

                $attendanceType = 'all';
                $allowedBranches = null;

                if (!$isDayOff) {
                    $attendanceType = $request->input('area_date', 'all');
                    if ($attendanceType === 'one') {
                        $allowedBranches = array_filter([$request->input('branch_one_date')]);
                    } elseif ($attendanceType === 'some') {
                        $allowedBranches = array_filter($request->input('branch_some_date', []));
                    }
                }

                foreach ($employees as $employeeId) {
                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'date' => $date->toDateString(),
                        ],
                        [
                            'company_id' => $company->id,
                            'shift_id' => $shiftId ?: null,
                            'is_day_off' => $isDayOff,
                            'attendance_type' => $attendanceType,
                            'allowed_branches' => $allowedBranches,
                        ]
                    );
                }
            }
        });

        return redirect()->route('owner.management.schedules.work.index')
            ->with('success', 'Jadwal kerja berhasil dibuat dan diterapkan.');
    }

    public function editWork(WorkSchedule $schedule)
    {
        $company = auth()->user()->company;
        if ($schedule->company_id !== $company->id) {
            abort(403);
        }

        $schedule->load(['employee.position:id,name', 'employee.division:id,name', 'shift']);
        $shifts = Shift::where('company_id', $company->id)->orderBy('name')->get();
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Schedules/Work/Edit', [
            'schedule' => $schedule,
            'shifts' => $shifts,
            'branches' => $branches,
        ]);
    }

    public function updateWork(Request $request, WorkSchedule $schedule)
    {
        $company = auth()->user()->company;
        if ($schedule->company_id !== $company->id) {
            abort(403);
        }

        $request->validate([
            'is_day_off' => 'required|boolean',
            'shift_id' => 'exclude_if:is_day_off,1|required|exists:shifts,id',
            'attendance_type' => 'exclude_if:is_day_off,1|required|in:all,one,some',
            'branch_one' => 'exclude_unless:attendance_type,one|required|exists:branches,id',
            'branch_some' => 'exclude_unless:attendance_type,some|required|array',
            'branch_some.*' => 'exists:branches,id',
        ]);

        $isDayOff = $request->boolean('is_day_off');
        $attendanceType = $isDayOff ? 'all' : $request->attendance_type;
        $allowedBranches = null;

        if (!$isDayOff) {
            if ($attendanceType === 'one') {
                $allowedBranches = array_filter([$request->branch_one]);
            } elseif ($attendanceType === 'some') {
                $allowedBranches = array_filter($request->branch_some);
            }
        }

        $schedule->update([
            'is_day_off' => $isDayOff,
            'shift_id' => $isDayOff ? null : $request->shift_id,
            'attendance_type' => $attendanceType,
            'allowed_branches' => $allowedBranches,
        ]);

        return redirect()->route('owner.management.schedules.work.index')
            ->with('success', 'Jadwal kerja karyawan berhasil diperbarui.');
    }

    public function destroyWork(WorkSchedule $schedule)
    {
        $company = auth()->user()->company;
        if ($schedule->company_id !== $company->id) {
            abort(403);
        }

        $schedule->delete();

        return redirect()->route('owner.management.schedules.work.index')
            ->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    // ==========================================
    // SWAP PERSONAL
    // ==========================================
    public function swapPersonal(Request $request)
    {
        $company = auth()->user()->company;
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        $query = SwapPersonal::with([
            'employee.branch:id,name',
            'employee.division:id,name',
            'employee.user:id,avatar,email',
        ])
            ->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }
        
        if ($request->filled('search')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $swaps = $query->orderBy('created_at', $sort)
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Schedules/SwapPersonal/Index', [
            'swaps' => $swaps,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'search' => $request->search,
                'sort' => $sort,
            ],
        ]);
    }

    public function showSwapPersonal($id)
    {
        $company = auth()->user()->company;
        $swap = SwapPersonal::with([
            'employee.branch:id,name',
            'employee.division:id,name',
            'employee.position:id,name',
            'approver:id,name,email',
        ])
        ->where('company_id', $company->id)
        ->findOrFail($id);

        $reqDate = Carbon::parse($swap->original_work_date)->format('Y-m-d');
        $tarDate = Carbon::parse($swap->target_work_date)->format('Y-m-d');

        $originalSchedule = WorkSchedule::with('shift')
            ->where('employee_id', $swap->employee_id)
            ->where('date', $reqDate)
            ->first();

        $targetSchedule = WorkSchedule::with('shift')
            ->where('employee_id', $swap->employee_id)
            ->where('date', $tarDate)
            ->first();

        return Inertia::render('Owner/Management/Schedules/SwapPersonal/Show', [
            'swap' => $swap,
            'originalSchedule' => $originalSchedule,
            'targetSchedule' => $targetSchedule,
        ]);
    }

    public function approveSwapPersonal($id)
    {
        $swap = SwapPersonal::where('company_id', auth()->user()->company->id)->findOrFail($id);
        
        DB::transaction(function () use ($swap) {
            $swap->update(['status' => 'approved', 'approved_by' => auth()->id()]);
            
            $schedule1 = WorkSchedule::where('employee_id', $swap->employee_id)
                ->where('date', Carbon::parse($swap->original_work_date)->format('Y-m-d'))
                ->first();
                
            $schedule2 = WorkSchedule::where('employee_id', $swap->employee_id)
                ->where('date', Carbon::parse($swap->target_work_date)->format('Y-m-d'))
                ->first();
                
            if ($schedule1 && $schedule2) {
                $tempShiftId = $schedule1->shift_id;
                $tempIsDayOff = $schedule1->is_day_off;
                
                $schedule1->update([
                    'shift_id' => $schedule2->shift_id,
                    'is_day_off' => $schedule2->is_day_off,
                ]);
                
                $schedule2->update([
                    'shift_id' => $tempShiftId,
                    'is_day_off' => $tempIsDayOff,
                ]);
            }
        });

        return redirect()->back()
            ->with('success', 'Tukar jadwal mandiri telah disetujui.');
    }

    public function rejectSwapPersonal($id)
    {
        $swap = SwapPersonal::where('company_id', auth()->user()->company->id)->findOrFail($id);
        $swap->update(['status' => 'rejected', 'approved_by' => auth()->id()]);

        return redirect()->back()
            ->with('success', 'Tukar jadwal mandiri telah ditolak.');
    }

    // ==========================================
    // SWAP TEAM
    // ==========================================
    public function swapTeam(Request $request)
    {
        $company = auth()->user()->company;
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        $query = SwapTeam::with([
            'requestor.branch:id,name',
            'requestor.division:id,name',
            'requestor.user:id,avatar,email',
            'targetEmployee.branch:id,name',
            'targetEmployee.division:id,name',
            'targetEmployee.user:id,avatar,email',
        ])->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('requestor', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('requestor', function($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('requestor', function($qr) use ($search) {
                    $qr->where('name', 'like', "%{$search}%");
                })->orWhereHas('targetEmployee', function($qt) use ($search) {
                    $qt->where('name', 'like', "%{$search}%");
                });
            });
        }

        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $swaps = $query->orderBy('created_at', $sort)
            ->paginate($request->input('per_page', 10))
            ->withQueryString();
        
        return Inertia::render('Owner/Management/Schedules/SwapTeam/Index', [
            'swaps' => $swaps,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'search' => $request->search,
                'sort' => $sort,
            ],
        ]);
    }

    public function showSwapTeam($id)
    {
        $company = auth()->user()->company;
        $swap = SwapTeam::with([
            'requestor.branch:id,name',
            'requestor.division:id,name',
            'requestor.position:id,name',
            'targetEmployee.branch:id,name',
            'targetEmployee.division:id,name',
            'targetEmployee.position:id,name',
            'approver:id,name,email',
        ])
        ->where('company_id', $company->id)
        ->findOrFail($id);

        $reqDate = Carbon::parse($swap->requestor_work_date)->format('Y-m-d');
        $tarDate = Carbon::parse($swap->target_work_date)->format('Y-m-d');

        $requestorSchedule = WorkSchedule::with('shift')
            ->where('employee_id', $swap->requestor_id)
            ->where('date', $reqDate)
            ->first();

        $targetSchedule = WorkSchedule::with('shift')
            ->where('employee_id', $swap->target_employee_id)
            ->where('date', $tarDate)
            ->first();

        return Inertia::render('Owner/Management/Schedules/SwapTeam/Show', [
            'swap' => $swap,
            'requestorSchedule' => $requestorSchedule,
            'targetSchedule' => $targetSchedule,
        ]);
    }

    public function approveSwapTeam($id)
    {
        $swap = SwapTeam::where('company_id', auth()->user()->company->id)->findOrFail($id);
        
        DB::transaction(function () use ($swap) {
            $swap->update(['status' => 'approved', 'approved_by' => auth()->id()]);
            
            $reqDate = Carbon::parse($swap->requestor_work_date)->format('Y-m-d');
            $tarDate = Carbon::parse($swap->target_work_date)->format('Y-m-d');

            $scheduleReqDay1 = WorkSchedule::where('employee_id', $swap->requestor_id)
                ->where('date', $reqDate)
                ->first();
                
            $scheduleTarDay1 = WorkSchedule::where('employee_id', $swap->target_employee_id)
                ->where('date', $reqDate)
                ->first();
                
            $scheduleReqDay2 = WorkSchedule::where('employee_id', $swap->requestor_id)
                ->where('date', $tarDate)
                ->first();
                
            $scheduleTarDay2 = WorkSchedule::where('employee_id', $swap->target_employee_id)
                ->where('date', $tarDate)
                ->first();
                
            if ($scheduleReqDay1 && $scheduleTarDay1) {
                $tempShiftId = $scheduleReqDay1->shift_id;
                $tempIsDayOff = $scheduleReqDay1->is_day_off;
                
                $scheduleReqDay1->update([
                    'shift_id' => $scheduleTarDay1->shift_id,
                    'is_day_off' => $scheduleTarDay1->is_day_off,
                ]);
                
                $scheduleTarDay1->update([
                    'shift_id' => $tempShiftId,
                    'is_day_off' => $tempIsDayOff,
                ]);
            }
            
            if ($reqDate !== $tarDate) {
                if ($scheduleReqDay2 && $scheduleTarDay2) {
                    $tempShiftId = $scheduleReqDay2->shift_id;
                    $tempIsDayOff = $scheduleReqDay2->is_day_off;
                    
                    $scheduleReqDay2->update([
                        'shift_id' => $scheduleTarDay2->shift_id,
                        'is_day_off' => $scheduleTarDay2->is_day_off,
                    ]);
                    
                    $scheduleTarDay2->update([
                        'shift_id' => $tempShiftId,
                        'is_day_off' => $tempIsDayOff,
                    ]);
                }
            }
        });

        return redirect()->back()
            ->with('success', 'Tukar jadwal tim telah disetujui.');
    }

    public function rejectSwapTeam($id)
    {
        $swap = SwapTeam::where('company_id', auth()->user()->company->id)->findOrFail($id);
        $swap->update(['status' => 'rejected', 'approved_by' => auth()->id()]);

        return redirect()->back()
            ->with('success', 'Tukar jadwal tim telah ditolak.');
    }
}
