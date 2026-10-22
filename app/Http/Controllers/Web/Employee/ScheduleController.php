<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\WorkSchedule;
use App\Models\Shift;
use App\Models\Holiday;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use App\Models\Employee;
use App\Notifications\NewEmployeeSchedulePersonalSwapPendingRequest;
use App\Notifications\NewEmployeeScheduleTeamSwapPendingRequest;

class ScheduleController extends Controller
{
    /**
     * Display monthly work schedule and holidays for authenticated employee.
     */
    public function work(Request $request)
    {
        $employee = $request->user()->employee()->with(['branch', 'division'])->first();
        if (!$employee) abort(403);

        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $schedules = WorkSchedule::with('shift:id,name,clock_in,clock_out')
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $holidays = Holiday::where('company_id', $employee->company_id)
            ->where(function ($q) use ($employee) {
                $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('division_id')->orWhere('division_id', $employee->division_id);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = range(date('Y') - 1, date('Y') + 1);

        return Inertia::render('Employee/Schedules/Work', [
            'employee' => $employee,
            'schedules' => $schedules,
            'holidays' => $holidays,
            'month' => $month,
            'year' => $year,
            'months' => $months,
            'years' => $years,
        ]);
    }

    /**
     * Display personal schedule swaps history.
     */
    public function swapPersonal(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = SwapPersonal::where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('reason', 'like', "%{$search}%");
        }

        $swaps = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Employee/Schedules/SwapPersonal', [
            'swaps' => $swaps,
            'filters' => [
                'status' => $request->input('status', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Submit a new personal schedule swap request.
     */
    public function storePersonalSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'original_work_date' => 'required|date',
            'target_work_date' => 'required|date|different:original_work_date',
            'reason' => 'required|string|max:500',
        ], [
            'original_work_date.required' => 'Tanggal jadwal asal wajib dipilih.',
            'target_work_date.required' => 'Tanggal jadwal pengganti wajib dipilih.',
            'target_work_date.different' => 'Tanggal pengganti harus berbeda dari tanggal asal.',
            'reason.required' => 'Alasan penukaran jadwal wajib diisi.',
        ]);

        $originalDate = Carbon::parse($request->original_work_date)->format('Y-m-d');
        $targetDate = Carbon::parse($request->target_work_date)->format('Y-m-d');

        // Check for existing pending swap
        $existingSwap = SwapPersonal::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->where(function ($query) use ($originalDate, $targetDate) {
                $query->whereIn('original_work_date', [$originalDate, $targetDate])
                      ->orWhereIn('target_work_date', [$originalDate, $targetDate]);
            })
            ->first();

        if ($existingSwap) {
            return back()->withErrors([
                'original_work_date' => 'Salah satu tanggal (asal atau pengganti) sedang dalam proses permohonan lain yang masih menunggu.'
            ]);
        }

        $swap = SwapPersonal::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'original_work_date' => $originalDate,
            'target_work_date' => $targetDate,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // Send notification to Owner
        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new NewEmployeeSchedulePersonalSwapPendingRequest($swap));
        }

        return back()->with('success', 'Permohonan tukar jadwal personal berhasil diajukan. Menunggu persetujuan manajemen.');
    }

    /**
     * Display team schedule swaps history with colleagues.
     */
    public function swapTeam(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = SwapTeam::with(['requestor:id,name,nip', 'targetEmployee:id,name,nip'])
            ->where(function ($q) use ($employee) {
                $q->where('requestor_id', $employee->id)
                  ->orWhere('target_employee_id', $employee->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('requestor', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('targetEmployee', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $swaps = $query->latest()
            ->paginate(10)
            ->withQueryString();

        // Colleagues in same branch & division
        $colleagues = Employee::where('company_id', $employee->company_id)
            ->where('id', '!=', $employee->id)
            ->where('is_active', true)
            ->select('id', 'name', 'nip')
            ->orderBy('name')
            ->get();

        return Inertia::render('Employee/Schedules/SwapTeam', [
            'swaps' => $swaps,
            'colleagues' => $colleagues,
            'currentEmployeeId' => $employee->id,
            'filters' => [
                'status' => $request->input('status', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Submit a new team schedule swap request with a colleague.
     */
    public function storeTeamSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'target_employee_id' => 'required|exists:employees,id|different:' . $employee->id,
            'requestor_work_date' => 'required|date',
            'target_work_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ], [
            'target_employee_id.required' => 'Pilih rekan kerja yang ingin diajak bertukar jadwal.',
            'requestor_work_date.required' => 'Tanggal jadwal Anda wajib dipilih.',
            'target_work_date.required' => 'Tanggal jadwal rekan wajib dipilih.',
            'reason.required' => 'Alasan pertukaran shift wajib diisi.',
        ]);

        $requestorDate = Carbon::parse($request->requestor_work_date)->format('Y-m-d');
        $targetDate = Carbon::parse($request->target_work_date)->format('Y-m-d');

        // Check pending swap for requestor date
        $existingRequestorSwap = SwapTeam::where('status', 'pending')
            ->where(function ($q) use ($employee, $requestorDate) {
                $q->where('requestor_id', $employee->id)->where('requestor_work_date', $requestorDate)
                  ->orWhere('target_employee_id', $employee->id)->where('target_work_date', $requestorDate);
            })->exists();

        if ($existingRequestorSwap) {
            return back()->withErrors([
                'requestor_work_date' => 'Anda sudah memiliki permohonan tukar jadwal tim yang sedang menunggu pada tanggal tersebut.'
            ]);
        }

        $swap = SwapTeam::create([
            'company_id' => $employee->company_id,
            'requestor_id' => $employee->id,
            'target_employee_id' => $request->target_employee_id,
            'requestor_work_date' => $requestorDate,
            'target_work_date' => $targetDate,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // Send notification to Owner
        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new NewEmployeeScheduleTeamSwapPendingRequest($swap));
        }

        return back()->with('success', 'Permohonan tukar jadwal tim dengan rekan kerja berhasil diajukan.');
    }
}
