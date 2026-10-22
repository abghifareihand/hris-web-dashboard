<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\Shift;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee()->with(['branch', 'division', 'position'])->first();
        if (!$employee) abort(403, 'Profil karyawan tidak ditemukan.');

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        $attendancesQuery = Attendance::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Calculate Monthly Summary Stats
        $totalPresent = 0;
        $totalOnTime = 0;
        $totalLate = 0;
        $totalLeave = 0;
        $totalAlpha = 0;
        $totalWorkMinutes = 0;

        $formattedAttendances = $attendancesQuery->map(function ($att) use (
            &$totalPresent, &$totalOnTime, &$totalLate, &$totalLeave, &$totalAlpha, &$totalWorkMinutes
        ) {
            $status = strtolower($att->attendance_status ?? '');
            $hasClockIn = !is_null($att->clock_in_time);
            $lateMin = (int) ($att->late_minutes ?? 0);

            if ($status === 'leave' || $status === 'cuti' || $status === 'sick' || $status === 'sakit') {
                $totalLeave++;
            } elseif ($status === 'alpha' || $status === 'absent') {
                $totalAlpha++;
            } elseif ($hasClockIn || in_array($status, ['present', 'hadir', 'good', 'very_good', 'fair', 'late'])) {
                $totalPresent++;
                if ($lateMin > 0 || $status === 'late') {
                    $totalLate++;
                } else {
                    $totalOnTime++;
                }
            }

            // Work minutes calculation
            $workMin = (int) ($att->total_work_minutes ?? 0);
            if ($workMin === 0 && $att->clock_in_time && $att->clock_out_time) {
                $cIn = Carbon::parse($att->clock_in_time);
                $cOut = Carbon::parse($att->clock_out_time);
                $workMin = max(0, $cIn->diffInMinutes($cOut));
            }
            $totalWorkMinutes += $workMin;

            // Photo URLs
            $photoInUrl = null;
            if ($att->clock_in_photo) {
                $photoInUrl = str_starts_with($att->clock_in_photo, 'http')
                    ? $att->clock_in_photo
                    : asset('storage/' . $att->clock_in_photo);
            }

            $photoOutUrl = null;
            if ($att->clock_out_photo) {
                $photoOutUrl = str_starts_with($att->clock_out_photo, 'http')
                    ? $att->clock_out_photo
                    : asset('storage/' . $att->clock_out_photo);
            }

            $parsedDate = Carbon::parse($att->date);

            return [
                'id' => $att->id,
                'date' => $att->date ? $att->date->toDateString() : null,
                'date_formatted' => $parsedDate->locale('id')->translatedFormat('l, d M Y'),
                'day_name' => $parsedDate->locale('id')->translatedFormat('l'),
                'shift_name' => $att->shift ? $att->shift->name : 'Shift Reguler',
                'shift_clock_in' => $att->shift ? substr($att->shift->clock_in, 0, 5) : '08:00',
                'shift_clock_out' => $att->shift ? substr($att->shift->clock_out, 0, 5) : '17:00',
                'clock_in_time' => $att->clock_in_time ? Carbon::parse($att->clock_in_time)->format('H:i') : null,
                'clock_out_time' => $att->clock_out_time ? Carbon::parse($att->clock_out_time)->format('H:i') : null,
                'work_duration' => $workMin > 0 ? floor($workMin / 60) . 'j ' . ($workMin % 60) . 'm' : '-',
                'work_minutes' => $workMin,
                'late_minutes' => $lateMin,
                'attendance_status' => $att->attendance_status,
                'status_label' => $att->status_label ?? 'Hadir',
                'status_color' => $att->status_color ?? 'green',
                'clock_in_address' => $att->clock_in_address,
                'clock_out_address' => $att->clock_out_address,
                'clock_in_photo' => $photoInUrl,
                'clock_out_photo' => $photoOutUrl,
                'notes' => $att->notes,
            ];
        });

        // Filter by status if requested
        $statusFilter = $request->input('status', '');
        if ($statusFilter !== '') {
            $formattedAttendances = $formattedAttendances->filter(function ($item) use ($statusFilter) {
                $status = strtolower($item['attendance_status'] ?? '');
                $lateMin = (int) ($item['late_minutes'] ?? 0);
                if ($statusFilter === 'present') {
                    return (in_array($status, ['present', 'hadir', 'good', 'very_good']) || $item['clock_in_time']) && $lateMin <= 0;
                }
                if ($statusFilter === 'late') {
                    return $lateMin > 0 || in_array($status, ['late', 'terlambat', 'fair']);
                }
                if ($statusFilter === 'leave') {
                    return in_array($status, ['leave', 'cuti', 'sick', 'sakit']);
                }
                if ($statusFilter === 'alpha') {
                    return in_array($status, ['alpha', 'absent', 'poor']);
                }
                return true;
            })->values();
        }

        // Scheduled days in this month
        $scheduledDays = WorkSchedule::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_day_off', false)
            ->count();
        if ($scheduledDays === 0) $scheduledDays = 22;

        $stats = [
            'total_present' => $totalPresent,
            'total_ontime' => $totalOnTime,
            'total_late' => $totalLate,
            'total_leave' => $totalLeave,
            'total_alpha' => $totalAlpha,
            'scheduled_days' => $scheduledDays,
            'attendance_rate' => $scheduledDays > 0 ? min(100, round(($totalPresent / $scheduledDays) * 100)) : 100,
            'total_work_hours' => round($totalWorkMinutes / 60, 1),
        ];

        // Today's attendance & schedule
        $todayAttendance = Attendance::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $todaySchedule = WorkSchedule::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // Months and years for selector
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = range(date('Y') - 2, date('Y') + 1);

        // Paginate attendances (10 per page matching Owner standard)
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $formattedAttendances->count();
        $results = $formattedAttendances->forPage($page, $perPage)->values();

        $paginatedAttendances = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Employee/Attendances/Index', [
            'employee' => $employee,
            'attendances' => $paginatedAttendances,
            'stats' => $stats,
            'todayAttendance' => $todayAttendance,
            'todaySchedule' => $todaySchedule,
            'month' => $month,
            'year' => $year,
            'months' => $months,
            'years' => $years,
            'filters' => [
                'status' => $statusFilter,
            ],
        ]);
    }
}
