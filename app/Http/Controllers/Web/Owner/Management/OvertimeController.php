<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Branch;
use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OvertimeController extends Controller
{
    public function pending(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = Overtime::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.division:id,name',
            'employee.branch:id,name'
        ])
            ->where('company_id', $company->id)
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $pendingOvertimes = $query->latest('date')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Overtimes/Pending', [
            'pendingOvertimes' => $pendingOvertimes,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = Overtime::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.division:id,name',
            'employee.branch:id,name',
            'approvedBy:id,name'
        ])
            ->where('company_id', $company->id)
            ->where('status', '!=', 'pending');

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $overtimes = $query->latest('date')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Overtimes/Index', [
            'overtimes' => $overtimes,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $overtime = Overtime::where('company_id', $company->id)->findOrFail($id);

        if ($overtime->status !== 'pending') {
            return redirect()->back()->with('error', 'Hanya pengajuan lembur yang berstatus pending yang dapat disetujui.');
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan lembur berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $overtime = Overtime::where('company_id', $company->id)->findOrFail($id);

        $overtime->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan lembur telah ditolak.');
    }
}
