<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Overtime;
use App\Notifications\NewEmployeeOvertimePendingRequest;

class OvertimeController extends Controller
{
    /**
     * Display list of employee overtime requests with monthly statistics.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Overtime::with(['approvedBy:id,name'])
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $overtimes = $query->latest('date')->latest('id')->paginate(10)->withQueryString();

        $totalApprovedMinutes = Overtime::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('duration_minutes');
        $totalHours = round($totalApprovedMinutes / 60, 1);

        $pendingCount = Overtime::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $approvedCount = Overtime::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->count();

        return Inertia::render('Employee/Overtimes/Index', [
            'overtimes' => $overtimes,
            'totalHours' => $totalHours,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'filters' => [
                'status' => $request->input('status', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Submit a new overtime request.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'description' => 'required|string|max:1000',
        ], [
            'title.required' => 'Judul kegiatan lembur wajib diisi.',
            'date.required' => 'Tanggal lembur wajib diisi.',
            'start_time.required' => 'Jam mulai lembur wajib diisi.',
            'end_time.required' => 'Jam selesai lembur wajib diisi.',
            'description.required' => 'Keterangan tugas lembur wajib diisi.',
        ]);

        // Prevent duplicate pending or approved requests on the same date
        $exists = Overtime::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('date', $request->date)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'date' => 'Anda sudah memiliki pengajuan lembur aktif (menunggu / disetujui) pada tanggal tersebut.'
            ]);
        }

        $start = Carbon::parse($request->date . ' ' . $request->start_time);
        $end = Carbon::parse($request->date . ' ' . $request->end_time);

        // Handle case where end time wraps past midnight
        if ($end->lt($start)) {
            $end->addDay();
        }

        $durationMinutes = $start->diffInMinutes($end);

        if ($durationMinutes <= 0) {
            return back()->withErrors([
                'end_time' => 'Waktu selesai harus lebih besar dari waktu mulai.'
            ]);
        }

        $overtime = Overtime::create([
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

        // Send notification to Company Owner if configured
        if ($employee->company && $employee->company->owner) {
            $employee->company->owner->notify(new NewEmployeeOvertimePendingRequest($overtime));
        }

        return back()->with('success', 'Pengajuan lembur berhasil dikirim. Menunggu persetujuan manajemen.');
    }

    /**
     * Cancel a pending overtime request.
     */
    public function cancel(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $overtime = Overtime::where('employee_id', $employee->id)->findOrFail($id);

        if ($overtime->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan yang berstatus menunggu yang dapat dibatalkan.');
        }

        $overtime->update(['status' => 'cancelled']);

        return back()->with('success', 'Pengajuan lembur berhasil dibatalkan.');
    }
}
