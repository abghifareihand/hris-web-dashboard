<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReimbursementController extends Controller
{
    public function pending(Request $request)
    {
        $company = Auth::user()->company;
        if (!$company) {
            return redirect()->back()->with('error', 'Perusahaan tidak ditemukan.');
        }

        $query = Reimbursement::with(['employee:id,name,nip,branch_id,division_id', 'employee.division:id,name', 'employee.branch:id,name'])
            ->where('company_id', $company->id)
            ->where('status', 'pending');

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

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $pendingReimbursements = $query->latest('date')->paginate(10)->withQueryString();
        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        return Inertia::render('Owner/Finance/Reimbursements/Pending', [
            'pendingReimbursements' => $pendingReimbursements,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => $request->only(['search', 'branch_id', 'division_id', 'date']),
        ]);
    }

    public function index(Request $request)
    {
        $company = Auth::user()->company;
        if (!$company) {
            return redirect()->back()->with('error', 'Perusahaan tidak ditemukan.');
        }

        $query = Reimbursement::with(['employee:id,name,nip,branch_id,division_id', 'employee.division:id,name', 'employee.branch:id,name', 'approvedBy:id,name'])
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

        if ($request->filled('payout_method')) {
            $query->where('payout_method', $request->payout_method);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $reimbursements = $query->latest('date')->paginate(10)->withQueryString();
        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->get();

        $stats = [
            'total_approved' => Reimbursement::where('company_id', $company->id)->where('status', 'approved')->sum('amount'),
            'total_paid' => Reimbursement::where('company_id', $company->id)->where('status', 'paid')->sum('amount'),
            'total_rejected' => Reimbursement::where('company_id', $company->id)->where('status', 'rejected')->count(),
            'pending_count' => Reimbursement::where('company_id', $company->id)->where('status', 'pending')->count(),
        ];

        return Inertia::render('Owner/Finance/Reimbursements/Index', [
            'reimbursements' => $reimbursements,
            'branches' => $branches,
            'divisions' => $divisions,
            'stats' => $stats,
            'filters' => $request->only(['branch_id', 'division_id', 'status', 'payout_method', 'start_date', 'end_date', 'search']),
        ]);
    }

    public function create()
    {
        $company = Auth::user()->company;
        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->select('id', 'name', 'nip')
            ->orderBy('name')
            ->get();

        return Inertia::render('Owner/Finance/Reimbursements/Create', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:1000',
            'payout_method' => 'nullable|in:payroll,direct',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $company = Auth::user()->company;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reimbursements', 'public');
        }

        $payoutMethod = $request->input('payout_method', 'payroll');
        if (!in_array($payoutMethod, ['payroll', 'direct'])) {
            $payoutMethod = 'payroll';
        }

        Reimbursement::create([
            'company_id' => $company->id,
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => $payoutMethod === 'direct' ? 'paid' : 'approved',
            'payout_method' => $payoutMethod,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('owner.finance.reimbursements.index')->with('success', 'Klaim biaya berhasil dibuat.');
    }

    public function approve(Request $request, $id)
    {
        $company = Auth::user()->company;
        $reimbursement = Reimbursement::where('company_id', $company->id)->findOrFail($id);

        if ($reimbursement->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan klaim berstatus pending yang dapat disetujui.');
        }

        $payoutMethod = $request->input('payout_method', 'payroll');
        if (!in_array($payoutMethod, ['payroll', 'direct'])) {
            $payoutMethod = 'payroll';
        }

        $reimbursement->update([
            'status' => $payoutMethod === 'direct' ? 'paid' : 'approved',
            'payout_method' => $payoutMethod,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $msg = $payoutMethod === 'payroll' 
            ? 'Pengajuan klaim biaya berhasil disetujui untuk dicairkan bersama gaji bulanan (Payroll).'
            : 'Pengajuan klaim biaya berhasil disetujui dan ditandai lunas (Transfer Langsung).';

        return back()->with('success', $msg);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $company = Auth::user()->company;
        $reimbursement = Reimbursement::where('company_id', $company->id)->findOrFail($id);

        if ($reimbursement->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan klaim berstatus pending yang dapat ditolak.');
        }

        $reimbursement->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan klaim biaya berhasil ditolak.');
    }

    public function destroy($id)
    {
        $company = Auth::user()->company;
        $reimbursement = Reimbursement::where('company_id', $company->id)->findOrFail($id);

        if ($reimbursement->status === 'paid' || !empty($reimbursement->payroll_item_id)) {
            return redirect()->back()->with('error', 'Klaim biaya yang sudah terhubung dengan penggajian atau sudah berstatus PAID tidak dapat dihapus.');
        }

        if ($reimbursement->attachment && Storage::disk('public')->exists($reimbursement->attachment)) {
            Storage::disk('public')->delete($reimbursement->attachment);
        }

        $reimbursement->delete();

        return redirect()->back()->with('success', 'Data klaim biaya berhasil dihapus.');
    }
}
