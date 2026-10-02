<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Overtime;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Overtime::where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $overtimes = $query->latest('date')->paginate(10)->withQueryString();

        $totalApprovedMinutes = Overtime::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('duration_minutes');
        $totalHours = round($totalApprovedMinutes / 60, 1);

        $pendingCount = Overtime::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Employee/Overtimes/Index', [
            'overtimes' => $overtimes,
            'totalHours' => $totalHours,
            'pendingCount' => $pendingCount,
            'filters' => [
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        $start = Carbon::parse($request->date . ' ' . $request->start_time);
        $end = Carbon::parse($request->date . ' ' . $request->end_time);

        if ($end->lt($start)) {
            $end->addDay();
        }

        $durationMinutes = max(1, $start->diffInMinutes($end));

        Overtime::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'title' => $request->title,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $durationMinutes,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan lembur berhasil dikirim.');
    }
}
