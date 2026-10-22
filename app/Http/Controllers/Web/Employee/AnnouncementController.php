<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    /**
     * Display list of company announcements for the authenticated employee.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $today = Carbon::today()->toDateString();

        $query = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($employee) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('division_id')
                  ->orWhere('division_id', $employee->division_id);
            });

        // Filter status
        $status = $request->input('status', '');
        if ($status === 'active') {
            $query->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
        } elseif ($status === 'upcoming') {
            $query->where('start_date', '>', $today);
        } elseif ($status === 'past') {
            $query->where('end_date', '<', $today);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->latest('id')->paginate(10)->withQueryString();

        return Inertia::render('Employee/Announcements/Index', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $status,
            ],
        ]);
    }

    /**
     * Display a specific announcement detail.
     */
    public function show($id, Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $announcement = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $employee->company_id)
            ->findOrFail($id);

        return Inertia::render('Employee/Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }
}
