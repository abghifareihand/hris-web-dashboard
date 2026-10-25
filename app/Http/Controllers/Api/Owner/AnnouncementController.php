<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    /**
     * Get list of announcements (paginated or all).
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $query = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            if ($request->branch_id === 'all' || $request->branch_id === '0') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $request->branch_id);
            }
        }

        if ($request->filled('division_id')) {
            if ($request->division_id === 'all' || $request->division_id === '0') {
                $query->whereNull('division_id');
            } else {
                $query->where('division_id', $request->division_id);
            }
        }

        $today = Carbon::today()->toDateString();
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('start_date', '<=', $today)->where('end_date', '>=', $today);
            } elseif ($request->status === 'upcoming') {
                $query->where('start_date', '>', $today);
            } elseif ($request->status === 'expired' || $request->status === 'past') {
                $query->where('end_date', '<', $today);
            }
        }

        if ($request->filled('start_date')) {
            $query->where('end_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('start_date', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        // Unpaginated list
        if ($request->boolean('all')) {
            $announcements = $query->get()->map(function ($item) {
                return $this->formatAnnouncement($item);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua pengumuman berhasil diambil.',
                'data' => $announcements,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->formatAnnouncement($item);
        });

        return $this->paginatedResponse($paginated, 'Daftar pengumuman berhasil diambil.');
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable',
            'division_id' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'content.required' => 'Isi keterangan pengumuman wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal berakhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal mulai.',
            'image.image' => 'File lampiran harus berupa gambar.',
            'image.mimes' => 'Format gambar yang didukung: JPG, JPEG, PNG, WEBP.',
            'image.max' => 'Ukuran gambar maksimal adalah 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branchId = $this->parseNullableId($request->input('branch_id', $request->input('branch')));
        $divisionId = $this->parseNullableId($request->input('division_id', $request->input('division')));

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'title' => $request->title,
            'content' => $request->content,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
            'image' => $imagePath,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        $announcement->load(['branch:id,name', 'division:id,name']);

        // Notify targeted employees
        $empQuery = \App\Models\Employee::with('user')->where('company_id', $company->id)->where('status', 'active');
        if ($branchId) {
            $empQuery->where('branch_id', $branchId);
        }
        if ($divisionId) {
            $empQuery->where('division_id', $divisionId);
        }
        $targetEmployees = $empQuery->get();

        \App\Services\NotificationService::sendToEmployees($targetEmployees, [
            'title' => 'Pengumuman Baru',
            'message' => 'Pengumuman baru "' . $announcement->title . '" telah diterbitkan oleh perusahaan.',
            'type' => 'announcement',
            'route' => 'announcement_list',
            'entity_id' => $announcement->id,
            'entity_type' => 'announcement',
            'icon' => 'megaphone',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengumuman baru berhasil diterbitkan.',
            'data' => $this->formatAnnouncement($announcement),
        ], 201);
    }

    /**
     * Get detail of a specific announcement.
     */
    public function show(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $announcement = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->find($id);

        if (!$announcement) {
            return response()->json([
                'status' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail pengumuman berhasil diambil.',
            'data' => $this->formatAnnouncement($announcement),
        ]);
    }

    /**
     * Update an existing announcement.
     * Note: Uses POST method because of multipart/form-data for image upload.
     */
    public function update(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $announcement = Announcement::where('company_id', $company->id)->find($id);
        if (!$announcement) {
            return response()->json([
                'status' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable',
            'division_id' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'content.required' => 'Isi keterangan pengumuman wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal berakhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal mulai.',
            'image.image' => 'File lampiran harus berupa gambar.',
            'image.mimes' => 'Format gambar yang didukung: JPG, JPEG, PNG, WEBP.',
            'image.max' => 'Ukuran gambar maksimal adalah 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branchId = $this->parseNullableId($request->input('branch_id', $request->input('branch', $announcement->branch_id)));
        $divisionId = $this->parseNullableId($request->input('division_id', $request->input('division', $announcement->division_id)));

        $data = [
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'title' => $request->title,
            'content' => $request->content,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
        ];

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->boolean('remove_image')) {
            if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
                Storage::disk('public')->delete($announcement->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
                Storage::disk('public')->delete($announcement->image);
            }
            $data['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($data);
        $announcement->load(['branch:id,name', 'division:id,name']);

        return response()->json([
            'status' => true,
            'message' => 'Pengumuman berhasil diperbarui.',
            'data' => $this->formatAnnouncement($announcement),
        ]);
    }

    /**
     * Delete an existing announcement.
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $announcement = Announcement::where('company_id', $company->id)->find($id);
        if (!$announcement) {
            return response()->json([
                'status' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pengumuman berhasil dihapus.',
        ]);
    }

    /**
     * Parse nullable ID for branch and division.
     */
    private function parseNullableId($value): ?int
    {
        if ($value === null || $value === '' || $value === 'all' || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Format announcement array for mobile Flutter consistency.
     */
    private function formatAnnouncement(Announcement $announcement): array
    {
        $start = Carbon::parse($announcement->start_date);
        $end = Carbon::parse($announcement->end_date);
        $today = Carbon::today();

        $status = 'active';
        $statusLabel = 'Aktif';
        if ($today->lt($start)) {
            $status = 'upcoming';
            $statusLabel = 'Mendatang';
        } elseif ($today->gt($end)) {
            $status = 'expired';
            $statusLabel = 'Selesai';
        }

        $dateFormatted = $start->isSameDay($end)
            ? $start->isoFormat('D MMMM Y')
            : $start->isoFormat('D MMMM') . ' - ' . $end->isoFormat('D MMMM Y');

        return [
            'id' => $announcement->id,
            'company_id' => $announcement->company_id,
            'title' => $announcement->title,
            'content' => $announcement->content,
            'image' => $announcement->image,
            'image_url' => $announcement->image_url,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'date_formatted' => $dateFormatted,
            'status' => $status,
            'status_label' => $statusLabel,
            'is_active' => (bool) $announcement->is_active,
            'branch_id' => $announcement->branch_id,
            'branch_name' => $announcement->branch ? $announcement->branch->name : 'Semua Cabang',
            'division_id' => $announcement->division_id,
            'division_name' => $announcement->division ? $announcement->division->name : 'Semua Divisi',
            'branch' => $announcement->branch ? [
                'id' => $announcement->branch->id,
                'name' => $announcement->branch->name,
            ] : null,
            'division' => $announcement->division ? [
                'id' => $announcement->division->id,
                'name' => $announcement->division->name,
            ] : null,
            'created_at' => $announcement->created_at ? Carbon::parse($announcement->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $announcement->updated_at ? Carbon::parse($announcement->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
