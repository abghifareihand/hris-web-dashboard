<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Check camera mode specifically before opening camera in Mobile App.
     */
    public function checkCamera(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $setting = AttendanceSetting::where('company_id', $employee->company_id)->first();
        $cameraMode = $setting ? $setting->camera_mode : 'both';

        return response()->json([
            'status' => true,
            'message' => 'Mode kamera absensi berhasil diambil.',
            'data' => [
                'camera_mode' => $cameraMode,
                'allow_front' => in_array($cameraMode, ['front', 'both']),
                'allow_back' => in_array($cameraMode, ['back', 'both']),
            ]
        ]);
    }



    /**
     * Get Today's Status (Jadwal & Absensi)
     */
    public function todayStatus(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $today = Carbon::today()->format('Y-m-d');
        
        // 1. Cek Holiday (Libur Nasional / Perusahaan)
        $holiday = Holiday::where('company_id', $employee->company_id)
            ->where(function($q) use ($employee) {
                $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function($q) use ($employee) {
                $q->whereNull('division_id')->orWhere('division_id', $employee->division_id);
            })
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        // 2. Cek Schedule
        $schedule = WorkSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // 3. Cek Absensi Hari Ini
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // 4. Cek Setting Perusahaan
        $setting = AttendanceSetting::where('company_id', $employee->company_id)->first();


        $canClockIn = false;
        $canClockOut = false;
        $clockInMessage = '';

        if ($attendance && $attendance->clock_out_time) {
            $canClockIn = false;
            $canClockOut = false;
            $clockInMessage = 'Anda sudah menyelesaikan absensi hari ini.';
        } elseif ($attendance && !$attendance->clock_out_time) {
            $canClockIn = false;
            $canClockOut = true;
            $clockInMessage = 'Silakan lakukan absensi keluar.';
        } else {
            // Not clocked in yet
            if (!$schedule) {
                $canClockIn = false;
                $clockInMessage = 'Anda belum memiliki jadwal untuk hari ini.';
            } elseif ($holiday) {
                $canClockIn = false;
                $clockInMessage = 'Hari ini adalah hari libur (' . $holiday->name . ').';
            } elseif ($schedule->is_day_off) {
                $canClockIn = false;
                $clockInMessage = 'Hari ini adalah hari libur (Day Off) Anda.';
            } else {
                $canClockIn = true;
                $clockInMessage = 'Silakan lakukan absensi masuk.';

                if ($schedule->shift && $employee->division && $employee->division->is_attendance_schedule) {
                    $earlyWindow = $setting ? (int) $setting->early_clock_in_minutes : 60;
                    $shiftClockIn = Carbon::parse($today . ' ' . $schedule->shift->clock_in);
                    $earliestAllowed = $shiftClockIn->copy()->subMinutes($earlyWindow);
                    
                    if (Carbon::now()->lt($earliestAllowed)) {
                        $canClockIn = false;
                        $clockInMessage = 'Absen masuk baru dibuka pukul ' . $earliestAllowed->format('H:i') . ' (' . $earlyWindow . ' menit sebelum shift).';
                    }
                }
            }
        }

        $formattedSchedule = null;
        if ($schedule) {
            $allowedBranchesFormatted = [];
            if ($schedule->is_day_off) {
                $allowedBranchesFormatted = [];
            } elseif ($schedule->attendance_type === 'all') {
                $allowedBranchesFormatted = \App\Models\Branch::where('company_id', $employee->company_id)
                    ->get(['id', 'name', 'latitude', 'longitude', 'radius'])
                    ->map(function ($b) {
                        return [
                            'id' => $b->id,
                            'name' => $b->name,
                            'latitude' => $b->latitude !== null ? (string) $b->latitude : null,
                            'longitude' => $b->longitude !== null ? (string) $b->longitude : null,
                            'radius' => (int) ($b->radius ?? 50),
                        ];
                    })
                    ->toArray();
            } elseif (is_array($schedule->allowed_branches) && count($schedule->allowed_branches) > 0) {
                $allowedBranchesFormatted = \App\Models\Branch::whereIn('id', $schedule->allowed_branches)
                    ->get(['id', 'name', 'latitude', 'longitude', 'radius'])
                    ->map(function ($b) {
                        return [
                            'id' => $b->id,
                            'name' => $b->name,
                            'latitude' => $b->latitude !== null ? (string) $b->latitude : null,
                            'longitude' => $b->longitude !== null ? (string) $b->longitude : null,
                            'radius' => (int) ($b->radius ?? 50),
                        ];
                    })
                    ->toArray();
            }

            $formattedSchedule = [
                'id' => $schedule->id,
                'company_id' => $schedule->company_id,
                'employee_id' => $schedule->employee_id,
                'shift_id' => $schedule->shift_id,
                'date' => $schedule->date ? \Carbon\Carbon::parse($schedule->date)->format('Y-m-d') : null,
                'is_day_off' => $schedule->is_day_off,
                'attendance_type' => $schedule->attendance_type,
                'allowed_branches' => $allowedBranchesFormatted,
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
                'shift' => $schedule->shift,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Status hari ini berhasil diambil.',
            'data' => [
                'date' => $today,
                'holiday' => $holiday,
                'schedule' => $formattedSchedule,
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'clock_in_time' => $attendance->clock_in_time,
                    'clock_out_time' => $attendance->clock_out_time,
                    'late_minutes' => (int) $attendance->late_minutes,
                    'early_leaving_minutes' => (int) $attendance->early_leaving_minutes,
                    'total_work_minutes' => (int) $attendance->total_work_minutes,
                    'total_violation_minutes' => (int) $attendance->total_violation_minutes,
                    'attendance_status' => $attendance->attendance_status,
                    'attendance_status_label' => $attendance->status_label,
                    'notes' => $attendance->notes,
                ] : null,
                'is_attendance_schedule' => $employee->division->is_attendance_schedule ?? false,
                'is_attendance_radius' => $employee->division->is_attendance_radius ?? false,
                'camera_mode' => $setting ? $setting->camera_mode : 'both',
                'can_clock_in' => $canClockIn,
                'can_clock_out' => $canClockOut,
                'clock_in_message' => $clockInMessage,
            ]
        ]);
    }

    /**
     * Clock In or Clock Out
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|image|max:2048', // Max 2MB
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        $division = $employee->division;
        $setting = AttendanceSetting::where('company_id', $employee->company_id)->first();
        
        // 1. Validasi Jadwal
        $schedule = WorkSchedule::with('shift')->where('employee_id', $employee->id)->where('date', $today)->first();
        
        if (!$schedule) {
            return response()->json(['status' => false, 'message' => 'Anda belum memiliki jadwal untuk hari ini.'], 400);
        }
        if ($schedule->is_day_off) {
            return response()->json(['status' => false, 'message' => 'Hari ini adalah hari libur (Day Off) Anda.'], 400);
        }

        $holiday = Holiday::where('start_date', '<=', $today)->where('end_date', '>=', $today)->first();
        if ($holiday) {
            return response()->json(['status' => false, 'message' => 'Hari ini adalah hari libur (' . $holiday->name . ').'], 400);
        }

        if ($division && $division->is_attendance_schedule) {
            if ($schedule->shift) {
                $earlyWindow = $setting ? (int) $setting->early_clock_in_minutes : 60;
                $shiftClockIn = Carbon::parse($today . ' ' . $schedule->shift->clock_in);
                $earliestAllowed = $shiftClockIn->copy()->subMinutes($earlyWindow);
                
                if (Carbon::now()->lt($earliestAllowed)) {
                    return response()->json(['status' => false, 'message' => 'Absen masuk baru dibuka pukul ' . $earliestAllowed->format('H:i') . '.'], 400);
                }
            }
        }

        // 2. Validasi Radius (jika divisi mewajibkan ATAU jadwal mensyaratkan cabang tertentu)
        $requiresRadius = false;
        if ($division && $division->is_attendance_radius) {
            $requiresRadius = true;
        }
        if ($schedule && in_array($schedule->attendance_type, ['one', 'some', 'all'])) {
            $requiresRadius = true;
        }

        if ($requiresRadius) {
            if ($schedule) {
                $isValidRadius = $this->validateScheduleRadius($request->latitude, $request->longitude, $schedule, $employee->company_id);
                if (!$isValidRadius) {
                    return response()->json(['status' => false, 'message' => 'Anda berada di luar area absensi yang diizinkan.'], 400);
                }
            } else {
                // If no schedule but radius required (by division), fallback to employee's main branch
                $isValidRadius = $this->checkBranchRadius($request->latitude, $request->longitude, $employee->branch_id);
                if (!$isValidRadius) {
                    return response()->json(['status' => false, 'message' => 'Anda berada di luar area cabang absensi.'], 400);
                }
            }
        }

        // Upload Photo
        $photoPath = $request->file('photo')->store('attendances', 'public');

        // Cek existing attendance
        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();

        if (!$attendance) {
            // CLOCK IN
            $lateMinutes = 0;
            if ($schedule && $schedule->shift) {
                $shiftClockIn = Carbon::parse($today . ' ' . $schedule->shift->clock_in);
                
                // Cek setting perusahaan
                $setting = AttendanceSetting::where('company_id', $employee->company_id)->first();
                $earlyWindow = $setting ? (int) $setting->early_clock_in_minutes : 60;
                $earliestAllowed = $shiftClockIn->copy()->subMinutes($earlyWindow);

                if ($now->lt($earliestAllowed)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Belum memasuki waktu absensi. Absen masuk baru dibuka pukul ' . $earliestAllowed->format('H:i') . '.'
                    ], 400);
                }

                if ($now->greaterThan($shiftClockIn)) {
                    $diffMins = (int) $shiftClockIn->diffInMinutes($now);
                    
                    // Cek toleransi keterlambatan dari pengaturan perusahaan
                    $setting = AttendanceSetting::where('company_id', $employee->company_id)->first();
                    $tolerance = $setting ? (int) $setting->late_tolerance_minutes : 10;

                    // Memaafkan 100% jika masih dalam batas toleransi
                    if ($diffMins > $tolerance) {
                        $lateMinutes = $diffMins;
                    } else {
                        $lateMinutes = 0;
                    }
                }
            }

            $attendance = Attendance::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'date' => $today,
                'shift_id' => $schedule ? $schedule->shift_id : null,
                'clock_in_time' => $now,
                'clock_in_latitude' => $request->latitude,
                'clock_in_longitude' => $request->longitude,
                'clock_in_photo' => $photoPath,
                'late_minutes' => $lateMinutes,
                'total_violation_minutes' => $lateMinutes,
                'notes' => 'Masuk: ' . ($request->notes ?: '-'),
                'attendance_status' => 'clocked_in'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Absen Masuk (Clock In) berhasil.'
            ]);
        } else {
            // CLOCK OUT
            if ($attendance->clock_out_time) {
                return response()->json(['status' => false, 'message' => 'Anda sudah menyelesaikan absensi hari ini.'], 400);
            }

            $earlyLeavingMinutes = 0;
            $effectiveStartTime = $attendance->clock_in_time;

            if ($schedule && $schedule->shift) {
                $shiftClockIn = Carbon::parse($today . ' ' . $schedule->shift->clock_in);
                $shiftClockOut = Carbon::parse($today . ' ' . $schedule->shift->clock_out);
                
                if ($now->lessThan($shiftClockOut)) {
                    $earlyLeavingMinutes = (int) $now->diffInMinutes($shiftClockOut);
                }

                // Jangan hitung waktu masuk yang kepagian sebagai jam kerja normal
                if ($attendance->clock_in_time->lt($shiftClockIn)) {
                    $effectiveStartTime = $shiftClockIn;
                }
            }

            $totalWorkMinutes = (int) $effectiveStartTime->diffInMinutes($now);
            $totalViolationMinutes = (int) ($attendance->late_minutes + $earlyLeavingMinutes);

            if ($attendance->late_minutes == 0 && $earlyLeavingMinutes == 0) {
                $statusStr = 'very_good';
            } elseif ($attendance->late_minutes > 0 && $earlyLeavingMinutes == 0) {
                $statusStr = 'fair'; // or good, depending on your business logic, keeping fair for now if late
            } elseif ($attendance->late_minutes == 0 && $earlyLeavingMinutes > 0) {
                $statusStr = 'fair';
            } else {
                $statusStr = 'poor';
            }

            $attendance->update([
                'clock_out_time' => $now,
                'clock_out_latitude' => $request->latitude,
                'clock_out_longitude' => $request->longitude,
                'clock_out_photo' => $photoPath,
                'early_leaving_minutes' => $earlyLeavingMinutes,
                'total_work_minutes' => $totalWorkMinutes,
                'total_violation_minutes' => $totalViolationMinutes,
                'notes' => ($attendance->notes ?: 'Masuk: -') . "\nPulang: " . ($request->notes ?: '-'),
                'attendance_status' => $statusStr
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Absen Pulang (Clock Out) berhasil.'
            ]);
        }
    }

    /**
     * Get Attendance History with pagination and filters
     */
    public function history(Request $request)
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
                'name' => $employee->user ? $employee->user->name : null,
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
                'clock_out_latitude' => $attendance->clock_out_latitude ? (string) $attendance->clock_out_latitude : null,
                'clock_out_longitude' => $attendance->clock_out_longitude ? (string) $attendance->clock_out_longitude : null,
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

    /**
     * Developer Helper: Check allowed branch locations and radius for today's attendance.
     */
    public function checkRadius(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $today = Carbon::today()->format('Y-m-d');
        $division = $employee->division;
        $schedule = WorkSchedule::with('shift')->where('employee_id', $employee->id)->where('date', $today)->first();

        $candidateBranches = collect();

        if ($schedule) {
            if ($schedule->attendance_type === 'all') {
                $candidateBranches = Branch::where('company_id', $employee->company_id)->get();
            } elseif (is_array($schedule->allowed_branches) && count($schedule->allowed_branches) > 0) {
                $candidateBranches = Branch::whereIn('id', $schedule->allowed_branches)->get();
            }
        } else {
            // Fallback to employee's main branch
            if ($employee->branch_id) {
                $branch = Branch::find($employee->branch_id);
                if ($branch) $candidateBranches->push($branch);
            }
        }

        $branchesData = $candidateBranches->map(function ($b) {
            return [
                'branch_id' => $b->id,
                'branch_name' => $b->name,
                'latitude' => (float) $b->latitude,
                'longitude' => (float) $b->longitude,
                'radius_meters' => (float) ($b->radius ?? 50),
                'address' => $b->address ?? null,
            ];
        });

        $isAttendanceRadiusRequired = $division ? (bool) $division->is_attendance_radius : false;
        $isAttendanceScheduleRequired = $division ? (bool) $division->is_attendance_schedule : false;

        return response()->json([
            'status' => true,
            'message' => 'Data lokasi dan radius absensi berhasil diambil.',
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->user ? $employee->user->name : null,
                    'division' => $division ? $division->name : null,
                    'is_attendance_radius_required' => $isAttendanceRadiusRequired,
                    'is_attendance_schedule_required' => $isAttendanceScheduleRequired,
                ],
                'schedule_today' => $schedule ? [
                    'id' => $schedule->id,
                    'attendance_type' => $schedule->attendance_type,
                    'is_day_off' => $schedule->is_day_off,
                    'shift' => $schedule->shift ? [
                        'name' => $schedule->shift->name,
                        'clock_in' => $schedule->shift->clock_in,
                        'clock_out' => $schedule->shift->clock_out,
                    ] : null,
                ] : null,
                'total_allowed_branches' => $branchesData->count(),
                'allowed_branches' => $branchesData,
            ]
        ]);
    }

    /**
     * Helper: Check Geofence Radius Based on Schedule
     */
    private function validateScheduleRadius($lat, $lng, $schedule, $companyId)
    {
        $attendanceType = $schedule->attendance_type;
        $allowedBranches = $schedule->allowed_branches; // array of branch IDs

        if ($attendanceType === 'all') {
            $branches = Branch::where('company_id', $companyId)->get();
            foreach ($branches as $branch) {
                if ($this->calculateDistance($lat, $lng, $branch->latitude, $branch->longitude) <= ($branch->radius ?? 50)) {
                    return true;
                }
            }
            return false;
        } elseif (is_array($allowedBranches) && count($allowedBranches) > 0) {
            $branches = Branch::whereIn('id', $allowedBranches)->get();
            foreach ($branches as $branch) {
                if ($this->calculateDistance($lat, $lng, $branch->latitude, $branch->longitude) <= ($branch->radius ?? 50)) {
                    return true;
                }
            }
            return false;
        }
        
        return false;
    }

    private function checkBranchRadius($lat, $lng, $branchId)
    {
        $branch = Branch::find($branchId);
        if (!$branch) return false;
        return $this->calculateDistance($lat, $lng, $branch->latitude, $branch->longitude) <= ($branch->radius ?? 50);
    }

    /**
     * Helper: Calculate Distance between two coordinates in meters (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
