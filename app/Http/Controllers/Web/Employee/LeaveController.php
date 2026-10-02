<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\LeaveRequest;
use App\Models\LeaveCategory;
use App\Models\LeaveBalance;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = LeaveRequest::with('leaveCategory:id,name,days_count')
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        $categories = LeaveCategory::where('company_id', $employee->company_id)->get();
        $balances = LeaveBalance::with('leaveCategory:id,name')->where('employee_id', $employee->id)->get();
        $totalBalance = (int) $balances->sum('balance');

        return Inertia::render('Employee/Leaves/Index', [
            'requests' => $requests,
            'categories' => $categories,
            'balances' => $balances,
            'totalBalance' => $totalBalance,
            'filters' => [
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function balances(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $balances = LeaveBalance::with('leaveCategory')->where('employee_id', $employee->id)->get();

        return Inertia::render('Employee/Leaves/Balances', [
            'balances' => $balances,
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'leave_category_id' => 'required|exists:leave_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $daysCount = $start->diffInDays($end) + 1;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leaves', 'public');
        }

        LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_category_id' => $request->leave_category_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_count' => $daysCount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan cuti berhasil dikirim dan menunggu persetujuan HRD.');
    }
}
