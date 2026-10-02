<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\Shift;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee()->with(['branch', 'division'])->first();
        if (!$employee) abort(403, 'Profil karyawan tidak ditemukan.');

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        $attendances = Attendance::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

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

        return Inertia::render('Employee/Attendances/Index', [
            'employee' => $employee,
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'todaySchedule' => $todaySchedule,
            'month' => $month,
            'year' => $year,
            'months' => $months,
            'years' => $years,
        ]);
    }

    public function clockIn(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'date' => $today,
        ]);

        if ($attendance->clock_in_time) {
            return back()->with('error', 'Anda sudah melakukan absen masuk hari ini.');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendances', 'public');
        }

        $schedule = WorkSchedule::with('shift')->where('employee_id', $employee->id)->where('date', $today)->first();
        $shiftId = $schedule->shift_id ?? null;

        // Calculate late minutes
        $lateMinutes = 0;
        if ($schedule && $schedule->shift) {
            $shiftIn = Carbon::parse($today . ' ' . $schedule->shift->clock_in);
            if ($now->gt($shiftIn)) {
                $lateMinutes = $shiftIn->diffInMinutes($now);
            }
        }

        $status = $lateMinutes > 0 ? 'late' : 'present';

        $attendance->company_id = $employee->company_id;
        $attendance->shift_id = $shiftId;
        $attendance->clock_in_time = $now->toTimeString();
        $attendance->clock_in_latitude = $request->input('latitude');
        $attendance->clock_in_longitude = $request->input('longitude');
        $attendance->clock_in_notes = $request->input('notes');
        $attendance->clock_in_photo = $photoPath;
        $attendance->late_minutes = $lateMinutes;
        $attendance->attendance_status = $status;
        $attendance->save();

        return back()->with('success', 'Absen masuk berhasil dicatat!');
    }

    public function clockOut(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in_time) {
            return back()->with('error', 'Anda belum melakukan absen masuk hari ini.');
        }

        if ($attendance->clock_out_time) {
            return back()->with('error', 'Anda sudah melakukan absen pulang hari ini.');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendances', 'public');
        }

        // Calculate total work minutes
        $clockIn = Carbon::parse($today . ' ' . $attendance->clock_in_time);
        $totalWorkMinutes = max(0, $clockIn->diffInMinutes($now));

        // Early leaving minutes
        $earlyLeaving = 0;
        $schedule = WorkSchedule::with('shift')->where('employee_id', $employee->id)->where('date', $today)->first();
        if ($schedule && $schedule->shift) {
            $shiftOut = Carbon::parse($today . ' ' . $schedule->shift->clock_out);
            if ($now->lt($shiftOut)) {
                $earlyLeaving = $now->diffInMinutes($shiftOut);
            }
        }

        $attendance->clock_out_time = $now->toTimeString();
        $attendance->clock_out_latitude = $request->input('latitude');
        $attendance->clock_out_longitude = $request->input('longitude');
        $attendance->clock_out_notes = $request->input('notes');
        $attendance->clock_out_photo = $photoPath;
        $attendance->total_work_minutes = $totalWorkMinutes;
        $attendance->early_leaving_minutes = $earlyLeaving;
        $attendance->save();

        return back()->with('success', 'Absen pulang berhasil dicatat. Selamat beristirahat!');
    }
}
