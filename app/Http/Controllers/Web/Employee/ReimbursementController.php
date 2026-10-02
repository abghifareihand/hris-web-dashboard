<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Reimbursement;

class ReimbursementController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Reimbursement::where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reimbursements = $query->latest('date')->paginate(10)->withQueryString();

        $totalApproved = (float) Reimbursement::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('amount');

        $totalPending = (float) Reimbursement::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->sum('amount');

        return Inertia::render('Employee/Reimbursements/Index', [
            'reimbursements' => $reimbursements,
            'totalApproved' => $totalApproved,
            'totalPending' => $totalPending,
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
            'amount' => 'required|numeric|min:1000',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reimbursements', 'public');
        }

        Reimbursement::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Klaim reimbursement berhasil diajukan.');
    }
}
