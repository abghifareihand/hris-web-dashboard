<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\WorkSchedule;
use App\Models\Employee;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Get all shifts for the owner's company (for shift selection dropdown)
     */
    public function getShifts(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $shifts = Shift::where('company_id', $company->id)->get(['id', 'name', 'clock_in', 'clock_out']);

        return response()->json([
            'status' => true,
            'message' => 'Data shift berhasil diambil.',
            'data' => $shifts
        ]);
    }

    /**
     * Get work schedules (filtered by employee, month, year)
     */
    public function getSchedules(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'nullable|integer|between:1,12',
            'year' => 'required|integer',
            'employee_id' => 'nullable|exists:employees,id',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $month = $request->month;
        $year = $request->year;

        $query = WorkSchedule::with(['employee:id,name,nip', 'shift:id,name,clock_in,clock_out'])
            ->where('company_id', $company->id)
            ->whereYear('date', $year);

        if ($request->filled('month')) {
            $query->whereMonth('date', $month);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id') || $request->filled('division_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                if ($request->filled('branch_id')) {
                    $q->where('branch_id', $request->branch_id);
                }
                if ($request->filled('division_id')) {
                    $q->where('division_id', $request->division_id);
                }
            });
        }

        $schedules = $query->orderBy('date', 'asc')->get();

        $branches = \App\Models\Branch::where('company_id', $company->id)->pluck('name', 'id');

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

        $holidayQuery = \App\Models\Holiday::where('company_id', $company->id)
            ->where(function($q) use ($month, $year, $request) {
                if ($request->filled('month')) {
                    $q->where(function($sq) use ($month, $year) {
                        $sq->whereMonth('start_date', $month)->whereYear('start_date', $year)
                           ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
                    });
                } else {
                    $q->where(function($sq) use ($year) {
                        $sq->whereYear('start_date', $year)
                           ->orWhereYear('end_date', $year);
                    });
                }
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

        return response()->json([
            'status' => true,
            'message' => 'Data jadwal kerja berhasil diambil.',
            'data' => $schedules,
            'holidays' => $holidays
        ]);
    }

    /**
     * Bulk store schedules for an employee (from preview)
     */
    public function bulkStore(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'schedules' => 'required|array',
            'schedules.*.date' => 'required|date',
            'schedules.*.shift_id' => 'nullable|exists:shifts,id',
            'schedules.*.is_day_off' => 'required|boolean',
            'schedules.*.attendance_type' => 'nullable|in:all,one,some',
            'schedules.*.allowed_branches' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Verify the employee belongs to the owner's company
        $employee = Employee::where('id', $request->employee_id)
            ->where('company_id', $company->id)
            ->first();

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Karyawan tidak ditemukan di perusahaan Anda.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $insertedSchedules = [];

            foreach ($request->schedules as $schedData) {
                $date = Carbon::parse($schedData['date'])->format('Y-m-d');
                $shiftId = $schedData['shift_id'] ?? null;
                $isDayOff = $schedData['is_day_off'] ?? false;
                
                $attendanceType = $schedData['attendance_type'] ?? 'all';
                $allowedBranches = $schedData['allowed_branches'] ?? null;

                if ($isDayOff) {
                    $attendanceType = 'all';
                    $allowedBranches = null;
                }

                $schedule = WorkSchedule::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $date,
                    ],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $isDayOff ? null : $shiftId,
                        'is_day_off' => $isDayOff,
                        'attendance_type' => $attendanceType,
                        'allowed_branches' => $allowedBranches,
                    ]
                );

                $insertedSchedules[] = $schedule;
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Jadwal kerja berhasil disimpan massal.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan jadwal kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Single Schedule
     */
    public function update(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $schedule = WorkSchedule::where('company_id', $company->id)->find($id);
        if (!$schedule) {
            return response()->json(['status' => false, 'message' => 'Jadwal kerja tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'shift_id' => 'nullable|exists:shifts,id',
            'is_day_off' => 'required|boolean',
            'attendance_type' => 'nullable|in:all,one,some',
            'allowed_branches' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $isDayOff = $request->is_day_off;
        $shiftId = $request->shift_id;
        $attendanceType = $request->input('attendance_type', 'all');
        $allowedBranches = $request->input('allowed_branches');

        if ($isDayOff) {
            $shiftId = null;
            $attendanceType = 'all';
            $allowedBranches = null;
        }

        $schedule->update([
            'shift_id' => $shiftId,
            'is_day_off' => $isDayOff,
            'attendance_type' => $attendanceType,
            'allowed_branches' => $allowedBranches,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jadwal kerja berhasil diperbarui.'
        ]);
    }

    /**
     * Delete Single Schedule
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $schedule = WorkSchedule::where('company_id', $company->id)->find($id);
        if (!$schedule) {
            return response()->json(['status' => false, 'message' => 'Jadwal kerja tidak ditemukan.'], 404);
        }

        $schedule->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jadwal kerja berhasil dihapus.'
        ]);
    }

    /**
     * Get Personal Swaps
     */
    public function getPersonalSwaps(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $query = SwapPersonal::with(['employee:id,name', 'approver:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('original_work_date', [$startDate, $endDate]);
        }

        $perPage = $this->getPerPage($request);
        $swaps = $query->latest()->paginate($perPage);

        return $this->paginatedResponse($swaps, 'Data pertukaran jadwal personal berhasil diambil.');
    }

    /**
     * Get Personal Swap Detail
     */
    public function getPersonalSwapDetail(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapPersonal::with(['employee:id,name', 'approver:id,name'])->where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tidak ditemukan.'], 404);
        }

        // Get schedules for original date and target date
        $originalSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->employee_id)
            ->where('date', $swap->original_work_date->format('Y-m-d'))
            ->first();

        $targetSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->employee_id)
            ->where('date', $swap->target_work_date->format('Y-m-d'))
            ->first();

        // Convert allowed branches for original schedule
        $branches = \App\Models\Branch::where('company_id', $company->id)->pluck('name', 'id');
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

        // Convert allowed branches for target schedule
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
     * Approve Personal Swap
     */
    public function approvePersonalSwap(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapPersonal::where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tidak ditemukan.'], 404);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($swap) {
            $swap->update([
                'status' => 'approved',
                'approved_by' => request()->user()->id,
            ]);

            $schedule1 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->employee_id, 'date' => $swap->original_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

            $schedule2 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->employee_id, 'date' => $swap->target_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

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
        });

        return response()->json([
            'status' => true,
            'message' => 'Pertukaran jadwal personal disetujui.'
        ]);
    }

    /**
     * Reject Personal Swap
     */
    public function rejectPersonalSwap(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapPersonal::where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tidak ditemukan.'], 404);
        }

        $swap->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pertukaran jadwal personal ditolak.'
        ]);
    }

    /**
     * Get Team Swaps
     */
    public function getTeamSwaps(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $query = SwapTeam::with(['requestor:id,name', 'targetEmployee:id,name', 'approver:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('requestor_work_date', [$startDate, $endDate]);
        }

        $perPage = $this->getPerPage($request);
        $swaps = $query->latest()->paginate($perPage);

        return $this->paginatedResponse($swaps, 'Data pertukaran jadwal tim berhasil diambil.');
    }

    /**
     * Get Team Swap Detail
     */
    public function getTeamSwapDetail(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapTeam::with(['requestor:id,name', 'targetEmployee:id,name', 'approver:id,name'])->where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tim tidak ditemukan.'], 404);
        }

        // Get schedules for requestor date and target employee's target date
        $requestorSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->requestor_id)
            ->where('date', $swap->requestor_work_date->format('Y-m-d'))
            ->first();

        $targetSchedule = WorkSchedule::with(['shift:id,name,clock_in,clock_out'])
            ->where('employee_id', $swap->target_employee_id)
            ->where('date', $swap->target_work_date->format('Y-m-d'))
            ->first();

        // Convert allowed branches for requestor schedule
        $branches = \App\Models\Branch::where('company_id', $company->id)->pluck('name', 'id');
        if ($requestorSchedule) {
            if ($requestorSchedule->attendance_type === 'all') {
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

        // Convert allowed branches for target schedule
        if ($targetSchedule) {
            if ($targetSchedule->attendance_type === 'all') {
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

    /**
     * Approve Team Swap
     */
    public function approveTeamSwap(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapTeam::where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tim tidak ditemukan.'], 404);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($swap) {
            $swap->update([
                'status' => 'approved',
                'approved_by' => request()->user()->id,
            ]);

            $scheduleReqDay1 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->requestor_id, 'date' => $swap->requestor_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

            $scheduleTarDay1 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->target_employee_id, 'date' => $swap->requestor_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

            $scheduleReqDay2 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->requestor_id, 'date' => $swap->target_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

            $scheduleTarDay2 = WorkSchedule::firstOrCreate(
                ['employee_id' => $swap->target_employee_id, 'date' => $swap->target_work_date->format('Y-m-d')],
                ['company_id' => $company->id, 'is_day_off' => true, 'attendance_type' => 'all']
            );

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

            // Lakukan swap hari ke-2 HANYA JIKA tanggalnya berbeda, agar tidak melakukan swap dua kali (revert)
            if ($swap->requestor_work_date->format('Y-m-d') !== $swap->target_work_date->format('Y-m-d')) {
                $tempShiftId2 = $scheduleReqDay2->shift_id;
                $tempIsDayOff2 = $scheduleReqDay2->is_day_off;

                $scheduleReqDay2->update([
                    'shift_id' => $scheduleTarDay2->shift_id,
                    'is_day_off' => $scheduleTarDay2->is_day_off,
                ]);

                $scheduleTarDay2->update([
                    'shift_id' => $tempShiftId2,
                    'is_day_off' => $tempIsDayOff2,
                ]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Pertukaran jadwal tim disetujui.'
        ]);
    }

    /**
     * Reject Team Swap
     */
    public function rejectTeamSwap(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $swap = SwapTeam::where('company_id', $company->id)->find($id);
        if (!$swap) {
            return response()->json(['status' => false, 'message' => 'Data pertukaran jadwal tim tidak ditemukan.'], 404);
        }

        $swap->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pertukaran jadwal tim ditolak.'
        ]);
    }
}
