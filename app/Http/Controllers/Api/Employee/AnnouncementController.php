<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get list of active announcements for the authenticated employee,
     * with full content and image URL embedded for direct display.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $query = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $employee->branch_id);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('division_id')
                  ->orWhere('division_id', $employee->division_id);
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->latest('id')->paginate($this->getPerPage($request));

        $announcements->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'image' => $item->image,
                'image_url' => $item->image_url,
                'start_date' => $item->start_date ? Carbon::parse($item->start_date)->format('Y-m-d') : null,
                'end_date' => $item->end_date ? Carbon::parse($item->end_date)->format('Y-m-d') : null,
                'branch_name' => $item->branch ? $item->branch->name : 'Semua Cabang',
                'division_name' => $item->division ? $item->division->name : 'Semua Divisi',
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return $this->paginatedResponse($announcements, 'Daftar pengumuman berhasil diambil.');
    }
}
