<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get Work Schedules
     */
    public function getSchedules(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $month = $request->input('month', \Carbon\Carbon::now()->month);
        $year = $request->input('year', \Carbon\Carbon::now()->year);

        $schedules = WorkSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $branches = \App\Models\Branch::where('company_id', $employee->company_id)->pluck('name', 'id');

        $schedules->transform(function ($schedule) use ($branches) {
            if ($schedule->is_day_off) {
                $schedule->allowed_branches = [];
                return $schedule;
            }

            if ($schedule->attendance_type === 'all') {
                $schedule->allowed_branches = ['Semua Cabang'];
            } elseif (is_array($schedule->allowed_branches)) {
                $branchNames = [];
                foreach ($schedule->allowed_branches as $branchId) {
                    if (isset($branches[$branchId])) {
                        $branchNames[] = $branches[$branchId];
                    }
                }
                $schedule->allowed_branches = $branchNames;
            }
            return $schedule;
        });

        $holidays = \App\Models\Holiday::where('company_id', $employee->company_id)
            ->where(function($q) use ($employee) {
                $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function($q) use ($employee) {
                $q->whereNull('division_id')->orWhere('division_id', $employee->division_id);
            })
            ->where(function($q) use ($month, $year) {
                $q->whereMonth('start_date', $month)->whereYear('start_date', $year)
                  ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
            })
            ->get(['id', 'name', 'start_date', 'end_date']);

        return response()->json([
            'status' => true,
            'message' => 'Data jadwal kerja berhasil diambil.',
            'data' => $schedules,
            'holidays' => $holidays
        ]);
    }

    /**
     * Get Colleagues (for team swap)
     */
    public function getColleagues(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $colleagues = Employee::with(['division', 'position'])
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->where('division_id', $employee->division_id)
            ->where('id', '!=', $employee->id)
            ->get();

        $formattedColleagues = $colleagues->map(function ($colleague) {
            return [
                'id' => $colleague->id,
                'name' => $colleague->name,
                'email' => $colleague->email,
                'division' => $colleague->division?->name,
                'position' => $colleague->position?->name,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Data rekan kerja berhasil diambil.',
            'data' => $formattedColleagues
        ]);
    }

    /**
     * Get Personal Swaps
     */
    public function getPersonalSwaps(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $swaps = SwapPersonal::where('employee_id', $employee->id)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('original_work_date', [$startDate, $endDate]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage($request));

        return $this->paginatedResponse($swaps, 'Data tukar jadwal pribadi berhasil diambil.');
    }

    /**
     * Store Personal Swap
     */
    public function storePersonalSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $minDate = Carbon::today()->addDays(2)->toDateString();

        $validator = Validator::make($request->all(), [
            'original_work_date' => 'required|date|after_or_equal:' . $minDate,
            'target_work_date' => 'required|date|different:original_work_date|after_or_equal:' . $minDate,
            'reason' => 'nullable|string',
        ], [
            'original_work_date.after_or_equal' => 'Tanggal jadwal yang ditukar minimal H+2 dari hari ini.',
            'target_work_date.after_or_equal' => 'Tanggal baru minimal H+2 dari hari ini.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $originalDate = Carbon::parse($request->original_work_date)->format('Y-m-d');
        $targetDate = Carbon::parse($request->target_work_date)->format('Y-m-d');

        $scheduleOriginal = WorkSchedule::where('employee_id', $employee->id)->where('date', $originalDate)->first();
        if (!$scheduleOriginal) {
            return response()->json([
                'status' => false,
                'message' => "Jadwal pada tanggal " . Carbon::parse($originalDate)->format('d') . " tidak ditemukan."
            ], 422);
        }

        $scheduleTarget = WorkSchedule::where('employee_id', $employee->id)->where('date', $targetDate)->first();
        if (!$scheduleTarget) {
            return response()->json([
                'status' => false,
                'message' => "Jadwal pada tanggal " . Carbon::parse($targetDate)->format('d') . " tidak ditemukan."
            ], 422);
        }

        if ($scheduleOriginal->is_day_off === $scheduleTarget->is_day_off) {
            return response()->json([
                'status' => false,
                'message' => 'Tukar jadwal pribadi hanya bisa dilakukan antara hari kerja dan hari libur.'
            ], 422);
        }

        // Check for existing pending swap for these dates
        $existingSwap = SwapPersonal::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->where(function ($query) use ($originalDate, $targetDate) {
                $query->whereIn('original_work_date', [$originalDate, $targetDate])
                      ->orWhereIn('target_work_date', [$originalDate, $targetDate]);
            })
            ->first();

        if ($existingSwap) {
            return response()->json([
                'status' => false,
                'message' => 'Salah satu tanggal (awal atau tujuan) sedang dalam status pending pada pengajuan lain.'
            ], 422);
        }

        $swap = SwapPersonal::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'original_work_date' => $originalDate,
            'target_work_date' => $targetDate,
            'reason' => $request->reason ?? '-',
            'status' => 'pending',
        ]);

        // Kirim notifikasi ke Owner
        $company = \App\Models\Company::find($employee->company_id);
        if ($company && $company->owner) {
            $company->owner->notify(new \App\Notifications\NewEmployeeSchedulePersonalSwapPendingRequest($swap));
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan tukar jadwal pribadi berhasil dikirim.'
        ]);
    }

    /**
     * Get Team Swaps
     */
    public function getTeamSwaps(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $swaps = SwapTeam::with(['targetEmployee:id,name,nip'])
            ->where('requestor_id', $employee->id)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('requestor_work_date', [$startDate, $endDate]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage($request));

        return $this->paginatedResponse($swaps, 'Data tukar jadwal tim berhasil diambil.');
    }

    /**
     * Store Team Swap
     */
    public function storeTeamSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses.'
            ], 403);
        }

        $minDate = Carbon::today()->addDays(2)->toDateString();

        $validator = Validator::make($request->all(), [
            'target_employee_id' => 'required|exists:employees,id',
            'requestor_work_date' => 'required|date|after_or_equal:' . $minDate,
            'target_work_date' => 'required|date|after_or_equal:' . $minDate,
            'reason' => 'nullable|string',
        ], [
            'requestor_work_date.after_or_equal' => 'Tanggal jadwal Anda minimal H+2 dari hari ini.',
            'target_work_date.after_or_equal' => 'Tanggal jadwal rekan minimal H+2 dari hari ini.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($employee->id == $request->target_employee_id) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak bisa menukar jadwal dengan diri sendiri. Gunakan fitur tukar jadwal pribadi.'
            ], 422);
        }

        $requestorDate = Carbon::parse($request->requestor_work_date)->format('Y-m-d');
        $targetDate = Carbon::parse($request->target_work_date)->format('Y-m-d');

        $requestorSchedule = WorkSchedule::where('employee_id', $employee->id)
            ->where('date', $requestorDate)->first();
        if (!$requestorSchedule) {
            return response()->json([
                'status' => false,
                'message' => "Anda tidak memiliki jadwal pada tanggal " . Carbon::parse($requestorDate)->format('d') . "."
            ], 422);
        }

        $targetSchedule = WorkSchedule::where('employee_id', $request->target_employee_id)
            ->where('date', $targetDate)->first();
        if (!$targetSchedule) {
            return response()->json([
                'status' => false,
                'message' => "Rekan kerja tidak memiliki jadwal pada tanggal " . Carbon::parse($targetDate)->format('d') . "."
            ], 422);
        }

        // Rule: Tukar jadwal tim hari dan shift yang sama ga bisa
        if ($requestorDate === $targetDate && $requestorSchedule->shift_id === $targetSchedule->shift_id) {
            return response()->json([
                'status' => false,
                'message' => 'Tukar jadwal tidak dapat dilakukan untuk hari dan shift yang sama.'
            ], 422);
        }

        // Rule: Pencegahan double shift hanya berlaku jika tukar jadwal BEDA TANGGAL
        if ($requestorDate !== $targetDate) {
            // Requestor tidak boleh memiliki jadwal aktif di target date (agar tidak double shift)
            $requestorTargetSchedule = WorkSchedule::where('employee_id', $employee->id)
                ->where('date', $targetDate)
                ->first();
            if ($requestorTargetSchedule && !$requestorTargetSchedule->is_day_off) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah memiliki jadwal kerja aktif pada tanggal rekan kerja Anda (' . Carbon::parse($targetDate)->format('d M Y') . ').'
                ], 422);
            }

            // Target tidak boleh memiliki jadwal aktif di requestor date (agar tidak double shift)
            $targetRequestorSchedule = WorkSchedule::where('employee_id', $request->target_employee_id)
                ->where('date', $requestorDate)
                ->first();
            if ($targetRequestorSchedule && !$targetRequestorSchedule->is_day_off) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rekan kerja Anda sudah memiliki jadwal kerja aktif pada tanggal Anda (' . Carbon::parse($requestorDate)->format('d M Y') . ').'
                ], 422);
            }
        }

        // Check if requestor's date is already involved in a pending swap
        $existingRequestorSwap = SwapTeam::where('status', 'pending')
            ->where(function ($q) use ($employee, $requestorDate) {
                $q->where('requestor_id', $employee->id)->where('requestor_work_date', $requestorDate)
                  ->orWhere('target_employee_id', $employee->id)->where('target_work_date', $requestorDate);
            })->exists();

        if ($existingRequestorSwap) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah memiliki pengajuan tukar jadwal tim yang pending pada tanggal Anda tersebut.'
            ], 422);
        }

        // Check if target's date is already involved in a pending swap
        $existingTargetSwap = SwapTeam::where('status', 'pending')
            ->where(function ($q) use ($request, $targetDate) {
                $q->where('requestor_id', $request->target_employee_id)->where('requestor_work_date', $targetDate)
                  ->orWhere('target_employee_id', $request->target_employee_id)->where('target_work_date', $targetDate);
            })->exists();

        if ($existingTargetSwap) {
            return response()->json([
                'status' => false,
                'message' => 'Rekan Anda sudah memiliki pengajuan tukar jadwal tim yang pending pada tanggal tersebut.'
            ], 422);
        }

        $swap = SwapTeam::create([
            'company_id' => $employee->company_id,
            'requestor_id' => $employee->id,
            'target_employee_id' => $request->target_employee_id,
            'requestor_work_date' => $requestorDate,
            'target_work_date' => $targetDate,
            'reason' => $request->reason ?? '-',
            'status' => 'pending',
        ]);

        // Kirim notifikasi ke Owner
        $company = \App\Models\Company::find($employee->company_id);
        if ($company && $company->owner) {
            $company->owner->notify(new \App\Notifications\NewEmployeeScheduleTeamSwapPendingRequest($swap));
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan tukar jadwal tim berhasil dikirim.'
        ]);
    }

    /**
     * Get Personal Swap Detail
     */
    public function getPersonalSwapDetail(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $swap = SwapPersonal::with(['employee:id,name', 'approver:id,name'])
            ->where('employee_id', $employee->id)
            ->find($id);

        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tidak ditemukan.'], 404);
        }

        $originalSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->employee_id)
            ->where('date', $swap->original_work_date->format('Y-m-d'))
            ->first();

        $targetSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->employee_id)
            ->where('date', $swap->target_work_date->format('Y-m-d'))
            ->first();

        $branches = \App\Models\Branch::where('company_id', $employee->company_id)->pluck('name', 'id');
        if ($originalSchedule) {
            if ($originalSchedule->is_day_off) {
                $originalSchedule->allowed_branches = [];
            } elseif ($originalSchedule->attendance_type === 'all') {
                $originalSchedule->allowed_branches = ['Semua Cabang'];
            } elseif (is_array($originalSchedule->allowed_branches)) {
                $branchNames = [];
                foreach ($originalSchedule->allowed_branches as $branchId) {
                    if (isset($branches[$branchId])) {
                        $branchNames[] = $branches[$branchId];
                    }
                }
                $originalSchedule->allowed_branches = $branchNames;
            }
        }
        if ($targetSchedule) {
            if ($targetSchedule->is_day_off) {
                $targetSchedule->allowed_branches = [];
            } elseif ($targetSchedule->attendance_type === 'all') {
                $targetSchedule->allowed_branches = ['Semua Cabang'];
            } elseif (is_array($targetSchedule->allowed_branches)) {
                $branchNames = [];
                foreach ($targetSchedule->allowed_branches as $branchId) {
                    if (isset($branches[$branchId])) {
                        $branchNames[] = $branches[$branchId];
                    }
                }
                $targetSchedule->allowed_branches = $branchNames;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail pertukaran jadwal personal berhasil diambil.',
            'data' => [
                'swap' => $swap,
                'original_schedule' => $originalSchedule,
                'target_schedule' => $targetSchedule
            ]
        ]);
    }

    /**
     * Get Team Swap Detail
     */
    public function getTeamSwapDetail(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Hanya employee yang bisa mengakses.'], 403);
        }

        $swap = SwapTeam::with(['requestor:id,name', 'targetEmployee:id,name', 'approver:id,name'])
            ->where(function($q) use ($employee) {
                $q->where('requestor_id', $employee->id)
                  ->orWhere('target_employee_id', $employee->id);
            })
            ->find($id);

        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tim tidak ditemukan.'], 404);
        }

        $requestorSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->requestor_id)
            ->where('date', $swap->requestor_work_date->format('Y-m-d'))
            ->first();

        $targetSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->target_employee_id)
            ->where('date', $swap->target_work_date->format('Y-m-d'))
            ->first();

        $branches = \App\Models\Branch::where('company_id', $employee->company_id)->pluck('name', 'id');
        if ($requestorSchedule) {
            if ($requestorSchedule->is_day_off) {
                $requestorSchedule->allowed_branches = [];
            } elseif ($requestorSchedule->attendance_type === 'all') {
                $requestorSchedule->allowed_branches = ['Semua Cabang'];
            } elseif (is_array($requestorSchedule->allowed_branches)) {
                $branchNames = [];
                foreach ($requestorSchedule->allowed_branches as $branchId) {
                    if (isset($branches[$branchId])) {
                        $branchNames[] = $branches[$branchId];
                    }
                }
                $requestorSchedule->allowed_branches = $branchNames;
            }
        }
        if ($targetSchedule) {
            if ($targetSchedule->is_day_off) {
                $targetSchedule->allowed_branches = [];
            } elseif ($targetSchedule->attendance_type === 'all') {
                $targetSchedule->allowed_branches = ['Semua Cabang'];
            } elseif (is_array($targetSchedule->allowed_branches)) {
                $branchNames = [];
                foreach ($targetSchedule->allowed_branches as $branchId) {
                    if (isset($branches[$branchId])) {
                        $branchNames[] = $branches[$branchId];
                    }
                }
                $targetSchedule->allowed_branches = $branchNames;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail pertukaran jadwal tim berhasil diambil.',
            'data' => [
                'swap' => $swap,
                'requestor_schedule' => $requestorSchedule,
                'target_schedule' => $targetSchedule
            ]
        ]);
    }
}
