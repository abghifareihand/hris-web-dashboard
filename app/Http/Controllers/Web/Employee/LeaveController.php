<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\LeaveRequest;
use App\Models\LeaveCategory;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403, 'Data karyawan tidak ditemukan.');

        $currentYear = date('Y');

        $query = LeaveRequest::with('leaveCategory:id,name,default_quota')
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_category_id')) {
            $query->where('leave_category_id', $request->leave_category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('reason', 'like', "%{$search}%");
        }

        $requests = $query->latest('id')->paginate(10)->withQueryString();

        $requests->getCollection()->transform(function ($req) {
            $start = Carbon::parse($req->start_date);
            $end = Carbon::parse($req->end_date);
            return [
                'id' => $req->id,
                'leave_category_id' => $req->leave_category_id,
                'category_name' => $req->leaveCategory ? $req->leaveCategory->name : 'Cuti',
                'start_date' => $req->start_date ? $req->start_date->toDateString() : null,
                'end_date' => $req->end_date ? $req->end_date->toDateString() : null,
                'date_range_formatted' => $start->locale('id')->translatedFormat('d M') . ' - ' . $end->locale('id')->translatedFormat('d M Y'),
                'days_count' => $req->days_count,
                'reason' => $req->reason,
                'status' => $req->status,
                'reject_reason' => $req->reject_reason,
                'attachment_url' => $req->attachment ? asset('storage/' . $req->attachment) : null,
                'created_at_formatted' => $req->created_at ? $req->created_at->locale('id')->translatedFormat('d M Y, H:i') : '-',
            ];
        });

        // Balances calculation based on (quota - used)
        $balances = LeaveBalance::with('leaveCategory')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get()
            ->map(function ($b) {
                $rem = max(0, $b->quota - $b->used);
                return [
                    'id' => $b->id,
                    'leave_category_id' => $b->leave_category_id,
                    'category_name' => $b->leaveCategory ? $b->leaveCategory->name : 'Cuti',
                    'quota' => (int) $b->quota,
                    'used' => (int) $b->used,
                    'remaining' => (int) $rem,
                ];
            });

        $totalBalance = (int) $balances->sum('remaining');

        // Categories available in this company
        $categories = LeaveCategory::where('company_id', $employee->company_id)
            ->get()
            ->map(function ($c) use ($balances) {
                $b = $balances->firstWhere('leave_category_id', $c->id);
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'remaining' => $b ? $b['remaining'] : $c->default_quota,
                ];
            });

        return Inertia::render('Employee/Leaves/Index', [
            'requests' => $requests,
            'categories' => $categories,
            'balances' => $balances,
            'totalBalance' => $totalBalance,
            'filters' => [
                'status' => $request->input('status', ''),
                'leave_category_id' => $request->input('leave_category_id', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    public function balances(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $currentYear = date('Y');
        $balances = LeaveBalance::with('leaveCategory')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'category_name' => $b->leaveCategory ? $b->leaveCategory->name : 'Cuti',
                    'quota' => $b->quota,
                    'used' => $b->used,
                    'remaining' => max(0, $b->quota - $b->used),
                ];
            });

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

        $category = LeaveCategory::where('company_id', $employee->company_id)
            ->where('id', $request->leave_category_id)
            ->firstOrFail();

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $daysCount = $start->diffInDays($end) + 1;

        // Check for overlapping active leave requests
        $overlapExists = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($overlapExists) {
            return back()->withErrors([
                'start_date' => 'Anda sudah memiliki pengajuan cuti aktif pada rentang tanggal tersebut.',
            ]);
        }

        // Check leave balance for current year
        $year = $start->year;
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_category_id', $category->id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            $remaining = $balance->quota - $balance->used;
            if ($daysCount > $remaining) {
                return back()->withErrors([
                    'leave_category_id' => 'Sisa kuota cuti Anda tidak mencukupi (tersisa ' . $remaining . ' hari, diajukan ' . $daysCount . ' hari).',
                ]);
            }
        }

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
