<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with(['employees' => function ($q) {
            $q->where('is_active', true);
        }, 'shifts', 'branches'])->get();

        foreach ($companies as $company) {
            $employees = $company->employees;
            if ($employees->isEmpty()) {
                continue;
            }

            // Ensure company has shifts
            $shifts = $company->shifts;
            if ($shifts->isEmpty()) {
                $shift1 = Shift::create([
                    'company_id' => $company->id,
                    'name' => 'Pagi (08:00 - 17:00)',
                    'clock_in' => '08:00',
                    'clock_out' => '17:00',
                ]);
                $shift2 = Shift::create([
                    'company_id' => $company->id,
                    'name' => 'Siang (13:00 - 21:00)',
                    'clock_in' => '13:00',
                    'clock_out' => '21:00',
                ]);
                $shifts = collect([$shift1, $shift2]);
            }

            // Generate schedules for:
            // Previous month (Sep 2026), current month (Oct 2026), and next month (Nov 2026)
            $start = Carbon::create(2026, 9, 1)->startOfMonth();
            $end = Carbon::create(2026, 11, 1)->endOfMonth();
            $period = CarbonPeriod::create($start, $end);

            $shiftArray = $shifts->values();
            $shiftCount = $shiftArray->count();

            foreach ($employees as $index => $employee) {
                // Vary patterns per employee
                $empShiftPrimary = $shiftArray[$index % $shiftCount];
                $empShiftSecondary = $shiftArray[($index + 1) % $shiftCount];

                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday

                    $isDayOff = false;
                    $selectedShift = null;

                    if ($dayOfWeek === 0) { // Sunday = Day Off
                        $isDayOff = true;
                    } elseif ($dayOfWeek === 6) { // Saturday = Day Off for most, some work
                        if (($index % 3) === 0) {
                            $selectedShift = $empShiftPrimary;
                            $isDayOff = false;
                        } else {
                            $isDayOff = true;
                        }
                    } else { // Weekdays: rotate shifts
                        $isDayOff = false;
                        $selectedShift = ($date->day % 2 === 0) ? $empShiftPrimary : $empShiftSecondary;
                    }

                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'date' => $dateStr,
                        ],
                        [
                            'company_id' => $company->id,
                            'shift_id' => $isDayOff ? null : ($selectedShift ? $selectedShift->id : null),
                            'is_day_off' => $isDayOff,
                            'attendance_type' => 'all',
                            'allowed_branches' => null,
                        ]
                    );
                }
            }
        }
    }
}
