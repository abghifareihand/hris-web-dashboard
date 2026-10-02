<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkSchedule;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Overtime;
use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceRecapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        if ($companies->isEmpty()) {
            $this->command->warn('Tidak ada perusahaan ditemukan.');
            return;
        }

        foreach ($companies as $company) {
            // 1. Ensure Attendance Setting exists
            AttendanceSetting::firstOrCreate(
                ['company_id' => $company->id],
                [
                    'status_very_good' => 'Sangat Baik',
                    'status_good' => 'Baik',
                    'status_fair' => 'Cukup',
                    'status_poor' => 'Kurang',
                ]
            );

            // 2. Ensure Shift exists
            $shift = Shift::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Shift Reguler'],
                [
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                ]
            );

            // 3. Ensure Leave Category exists
            $leaveCat = LeaveCategory::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Cuti Tahunan'],
                ['default_quota' => 12]
            );

            $employees = Employee::where('company_id', $company->id)->get();
            if ($employees->isEmpty()) {
                continue;
            }

            // Period: 1 September 2026 s.d 16 September 2026
            $startDate = Carbon::create(2026, 9, 1);
            $endDate = Carbon::create(2026, 9, 16);
            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($employees as $idx => $emp) {
                $profile = $idx % 4;
                $weekdayCount = 0;

                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $isWeekend = $date->isWeekend();

                    // Buat / update WorkSchedule
                    WorkSchedule::updateOrCreate(
                        ['employee_id' => $emp->id, 'date' => $dateStr],
                        [
                            'company_id' => $company->id,
                            'shift_id' => $isWeekend ? null : $shift->id,
                            'is_day_off' => $isWeekend,
                            'attendance_type' => 'all',
                        ]
                    );

                    if ($isWeekend) {
                        continue;
                    }

                    $weekdayCount++;

                    // Variasikan status kehadiran berdasarkan profil karyawan
                    $status = 'very_good';
                    $lateMinutes = 0;
                    $earlyLeavingMinutes = 0;
                    $clockIn = null;
                    $clockOut = null;

                    if ($profile === 0) {
                        // Profil Teladan: mostly very_good, occasional good, 1 fair
                        if ($weekdayCount <= 7) {
                            $status = 'very_good';
                            $clockIn = Carbon::parse("$dateStr 07:50:00");
                            $clockOut = Carbon::parse("$dateStr 17:10:00");
                        } elseif ($weekdayCount <= 9) {
                            $status = 'good';
                            $clockIn = Carbon::parse("$dateStr 07:59:00");
                            $clockOut = Carbon::parse("$dateStr 17:02:00");
                        } elseif ($weekdayCount == 10) {
                            $status = 'fair';
                            $lateMinutes = 15;
                            $clockIn = Carbon::parse("$dateStr 08:15:00");
                            $clockOut = Carbon::parse("$dateStr 17:05:00");
                        } else {
                            // Day 11 Cuti
                            $status = 'leave';
                        }
                    } elseif ($profile === 1) {
                        // Profil Normal: very_good, good, fair (pulang cepat), poor
                        if ($weekdayCount <= 5) {
                            $status = 'very_good';
                            $clockIn = Carbon::parse("$dateStr 07:45:00");
                            $clockOut = Carbon::parse("$dateStr 17:15:00");
                        } elseif ($weekdayCount <= 7) {
                            $status = 'good';
                            $clockIn = Carbon::parse("$dateStr 08:00:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        } elseif ($weekdayCount <= 9) {
                            $status = 'fair';
                            $earlyLeavingMinutes = 20;
                            $clockIn = Carbon::parse("$dateStr 07:55:00");
                            $clockOut = Carbon::parse("$dateStr 16:40:00");
                        } elseif ($weekdayCount == 10) {
                            $status = 'poor';
                            $lateMinutes = 35;
                            $earlyLeavingMinutes = 15;
                            $clockIn = Carbon::parse("$dateStr 08:35:00");
                            $clockOut = Carbon::parse("$dateStr 16:45:00");
                        } else {
                            $status = 'good';
                            $clockIn = Carbon::parse("$dateStr 07:58:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        }
                    } elseif ($profile === 2) {
                        // Profil Terlambat & Alpha
                        if ($weekdayCount <= 4) {
                            $status = 'very_good';
                            $clockIn = Carbon::parse("$dateStr 07:50:00");
                            $clockOut = Carbon::parse("$dateStr 17:10:00");
                        } elseif ($weekdayCount <= 6) {
                            $status = 'good';
                            $clockIn = Carbon::parse("$dateStr 07:57:00");
                            $clockOut = Carbon::parse("$dateStr 17:05:00");
                        } elseif ($weekdayCount <= 9) {
                            $status = 'fair';
                            $lateMinutes = 25;
                            $clockIn = Carbon::parse("$dateStr 08:25:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        } elseif ($weekdayCount == 10) {
                            $status = 'poor';
                            $lateMinutes = 45;
                            $earlyLeavingMinutes = 30;
                            $clockIn = Carbon::parse("$dateStr 08:45:00");
                            $clockOut = Carbon::parse("$dateStr 16:30:00");
                        } else {
                            $status = 'alpha';
                        }
                    } else {
                        // Profil Cuti, Lembur, & Kurang
                        if ($weekdayCount <= 4) {
                            $status = 'very_good';
                            $clockIn = Carbon::parse("$dateStr 07:52:00");
                            $clockOut = Carbon::parse("$dateStr 17:08:00");
                        } elseif ($weekdayCount <= 6) {
                            $status = 'good';
                            $clockIn = Carbon::parse("$dateStr 07:59:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        } elseif ($weekdayCount == 7) {
                            $status = 'fair';
                            $lateMinutes = 10;
                            $clockIn = Carbon::parse("$dateStr 08:10:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        } elseif ($weekdayCount <= 9) {
                            $status = 'leave';
                        } elseif ($weekdayCount == 10) {
                            $status = 'poor';
                            $lateMinutes = 50;
                            $clockIn = Carbon::parse("$dateStr 08:50:00");
                            $clockOut = Carbon::parse("$dateStr 17:00:00");
                        } else {
                            $status = 'very_good';
                            $clockIn = Carbon::parse("$dateStr 07:48:00");
                            $clockOut = Carbon::parse("$dateStr 17:12:00");
                        }
                    }

                    $totalViolationMinutes = $lateMinutes + $earlyLeavingMinutes;
                    $totalWorkMinutes = ($clockIn && $clockOut) ? (int) $clockIn->diffInMinutes($clockOut) : 0;

                    // Buat / update Attendance
                    Attendance::updateOrCreate(
                        ['employee_id' => $emp->id, 'date' => $dateStr],
                        [
                            'company_id' => $company->id,
                            'shift_id' => in_array($status, ['alpha', 'leave']) ? null : $shift->id,
                            'clock_in_time' => $clockIn,
                            'clock_out_time' => $clockOut,
                            'late_minutes' => $lateMinutes,
                            'early_leaving_minutes' => $earlyLeavingMinutes,
                            'total_violation_minutes' => $totalViolationMinutes,
                            'total_work_minutes' => $totalWorkMinutes,
                            'attendance_status' => $status,
                            'notes' => $status === 'leave' ? 'Cuti disetujui' : ($status === 'alpha' ? 'Tidak masuk kerja' : 'Presensi reguler'),
                        ]
                    );
                }

                // Tambahkan pengajuan Cuti resmi untuk yang berstatus leave
                if ($profile === 0) {
                    LeaveRequest::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'employee_id' => $emp->id,
                            'start_date' => '2026-09-15',
                        ],
                        [
                            'leave_category_id' => $leaveCat->id,
                            'end_date' => '2026-09-15',
                            'days_count' => 1,
                            'reason' => 'Keperluan keluarga mendesak',
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]
                    );
                } elseif ($profile === 3) {
                    LeaveRequest::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'employee_id' => $emp->id,
                            'start_date' => '2026-09-10',
                        ],
                        [
                            'leave_category_id' => $leaveCat->id,
                            'end_date' => '2026-09-11',
                            'days_count' => 2,
                            'reason' => 'Cuti tahunan refreshing',
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]
                    );
                }

                // Tambahkan data Lembur (Overtime) approved
                if ($profile === 0 || $profile === 1 || $profile === 3) {
                    Overtime::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'employee_id' => $emp->id,
                            'date' => '2026-09-04',
                        ],
                        [
                            'title' => 'Lembur Kejar Target Project',
                            'start_time' => '17:30:00',
                            'end_time' => '19:30:00',
                            'duration_minutes' => 120,
                            'description' => 'Menyelesaikan modul finance dan laporan',
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]
                    );

                    if ($profile === 0 || $profile === 3) {
                        Overtime::firstOrCreate(
                            [
                                'company_id' => $company->id,
                                'employee_id' => $emp->id,
                                'date' => '2026-09-09',
                            ],
                            [
                                'title' => 'Lembur Rekonsiliasi Data',
                                'start_time' => '17:30:00',
                                'end_time' => '19:00:00',
                                'duration_minutes' => 90,
                                'description' => 'Rekonsiliasi berkas operasional',
                                'status' => 'approved',
                                'approved_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }
}
