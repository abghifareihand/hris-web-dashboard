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

class ScheduleController extends Controller
{
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
            ->whereBetween('date', [$startDate, $endDate])
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

    public function swapPersonal(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $swaps = SwapPersonal::where('employee_id', $employee->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Employee/Schedules/SwapPersonal', [
            'swaps' => $swaps,
        ]);
    }

    public function storePersonalSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'original_work_date' => 'required|date',
            'target_work_date' => 'required|date|different:original_work_date',
            'reason' => 'required|string|max:500',
        ]);

        SwapPersonal::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'original_work_date' => $request->original_work_date,
            'target_work_date' => $request->target_work_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permohonan tukar jadwal personal berhasil diajukan.');
    }

    public function swapTeam(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $swaps = SwapTeam::with(['requestor:id,name,nip', 'targetEmployee:id,name,nip'])
            ->where(function ($q) use ($employee) {
                $q->where('requestor_id', $employee->id)
                  ->orWhere('target_employee_id', $employee->id);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Colleagues in same company
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
        ]);
    }

    public function storeTeamSwap(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'target_employee_id' => 'required|exists:employees,id|different:' . $employee->id,
            'requestor_work_date' => 'required|date',
            'target_work_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        SwapTeam::create([
            'company_id' => $employee->company_id,
            'requestor_id' => $employee->id,
            'target_employee_id' => $request->target_employee_id,
            'requestor_work_date' => $request->requestor_work_date,
            'target_work_date' => $request->target_work_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permohonan tukar jadwal tim dengan rekan kerja berhasil diajukan.');
    }
}
