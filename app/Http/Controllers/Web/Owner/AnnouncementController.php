<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the announcements.
     */
    public function index(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $query = Announcement::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('branch_id')) {
            if ($request->branch_id === 'all') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $request->branch_id);
            }
        }

        if ($request->filled('division_id')) {
            if ($request->division_id === 'all') {
                $query->whereNull('division_id');
            } else {
                $query->where('division_id', $request->division_id);
            }
        }

        if ($request->filled('start_date')) {
            $query->where('end_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('start_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $today = Carbon::today()->toDateString();
        $totalAnnouncements = Announcement::where('company_id', $company->id)->count();
        $activeAnnouncements = Announcement::where('company_id', $company->id)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();
        $allBranchesAnnouncements = Announcement::where('company_id', $company->id)
            ->whereNull('branch_id')
            ->count();
        $upcomingAnnouncements = Announcement::where('company_id', $company->id)
            ->where('start_date', '>', $today)
            ->count();

        $perPage = (int) $request->input('per_page', 10);
        $announcements = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Owner/Announcements/Index', [
            'announcements' => $announcements,
            'branches' => $branches,
            'divisions' => $divisions,
            'stats' => [
                'total' => $totalAnnouncements,
                'active' => $activeAnnouncements,
                'all_branches' => $allBranchesAnnouncements,
                'upcoming' => $upcomingAnnouncements,
            ],
            'filters' => [
                'branch_id' => $request->input('branch_id', ''),
                'division_id' => $request->input('division_id', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Owner/Announcements/Create', [
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $request->validate([
            'title' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'content.required' => 'Keterangan pengumuman wajib diisi.',
            'image.image' => 'File lampiran harus berupa gambar.',
            'image.mimes' => 'Format gambar yang didukung: JPG, JPEG, PNG, WEBP.',
            'image.max' => 'Ukuran gambar maksimal adalah 5 MB.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        Announcement::create([
            'company_id' => $company->id,
            'title' => $request->title,
            'branch_id' => $request->filled('branch_id') ? $request->branch_id : null,
            'division_id' => $request->filled('division_id') ? $request->division_id : null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'content' => $request->content,
            'image' => $imagePath,
            'is_active' => true,
        ]);

        return redirect()->route('owner.announcements.index')
            ->with('success', 'Pengumuman baru berhasil diterbitkan.');
    }

    /**
     * Display the specified announcement.
     */
    public function show($id)
    {
        $company = auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $announcement = Announcement::with(['branch', 'division'])
            ->where('company_id', $company->id)
            ->findOrFail($id);

        return Inertia::render('Owner/Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit($id)
    {
        $company = auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $announcement = Announcement::where('company_id', $company->id)->findOrFail($id);
        $branches = Branch::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();
        $divisions = Division::where('company_id', $company->id)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Owner/Announcements/Edit', [
            'announcement' => $announcement,
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, $id)
    {
        $company = auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $announcement = Announcement::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'content.required' => 'Keterangan pengumuman wajib diisi.',
            'image.image' => 'File lampiran harus berupa gambar.',
            'image.mimes' => 'Format gambar yang didukung: JPG, JPEG, PNG, WEBP.',
            'image.max' => 'Ukuran gambar maksimal adalah 5 MB.',
        ]);

        $data = [
            'title' => $request->title,
            'branch_id' => $request->filled('branch_id') ? $request->branch_id : null,
            'division_id' => $request->filled('division_id') ? $request->division_id : null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'content' => $request->content,
            'is_active' => true,
        ];

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

        return redirect()->route('owner.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy($id)
    {
        $company = auth()->user()?->company;
        if (!$company) abort(403, 'Akses ditolak.');

        $announcement = Announcement::where('company_id', $company->id)->findOrFail($id);

        if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->delete();

        return redirect()->route('owner.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
