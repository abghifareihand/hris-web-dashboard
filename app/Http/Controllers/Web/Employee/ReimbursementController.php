<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Reimbursement;
use App\Notifications\NewEmployeeReimbursementPendingRequest;

class ReimbursementController extends Controller
{
    /**
     * Display list of employee reimbursements with summary cards.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Reimbursement::with(['approvedBy:id,name'])
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('reason', 'like', "%{$search}%");
        }

        $reimbursements = $query->latest('date')->latest('id')->paginate(10)->withQueryString();

        $totalApproved = (float) Reimbursement::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $totalPending = (float) Reimbursement::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->sum('amount');

        $pendingCount = Reimbursement::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Employee/Reimbursements/Index', [
            'reimbursements' => $reimbursements,
            'totalApproved' => $totalApproved,
            'totalPending' => $totalPending,
            'pendingCount' => $pendingCount,
            'filters' => [
                'status' => $request->input('status', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Submit a new reimbursement claim.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1000',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'date.required' => 'Tanggal nota / kuitansi wajib diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran minimal Rp 1.000.',
            'reason.required' => 'Keterangan keperluan biaya wajib diisi.',
            'attachment.max' => 'Ukuran berkas lampiran maksimal 5MB.',
            'attachment.mimes' => 'Format berkas lampiran harus berupa PDF, JPG, atau PNG.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reimbursements', 'public');
        }

        $reimbursement = Reimbursement::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
            'payout_method' => null,
        ]);

        // Send notification to Company Owner if configured
        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new NewEmployeeReimbursementPendingRequest($reimbursement));
        }

        return back()->with('success', 'Klaim reimbursement berhasil diajukan. Menunggu verifikasi tim finance.');
    }

    /**
     * Cancel a pending reimbursement request.
     */
    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $reimbursement = Reimbursement::where('employee_id', $employee->id)->findOrFail($id);

        if ($reimbursement->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan klaim yang berstatus menunggu yang dapat dibatalkan.');
        }

        $reimbursement->update(['status' => 'cancelled']);

        return back()->with('success', 'Pengajuan klaim reimbursement berhasil dibatalkan.');
    }
}
