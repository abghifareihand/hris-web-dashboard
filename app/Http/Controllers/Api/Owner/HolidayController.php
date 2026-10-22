<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    /**
     * Get list of holidays (paginated or all).
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

        $query = Holiday::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('month')) {
            $query->where(function ($q) use ($request) {
                $q->whereMonth('start_date', $request->month)
                  ->orWhereMonth('end_date', $request->month);
            });
        }

        if ($request->filled('year')) {
            $query->where(function ($q) use ($request) {
                $q->whereYear('start_date', $request->year)
                  ->orWhereYear('end_date', $request->year);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        $query->orderBy('start_date', 'desc');

        // Unpaginated list (for calendar / picker)
        if ($request->boolean('all')) {
            $holidays = $query->get()->map(function ($holiday) {
                return $this->formatHoliday($holiday);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua hari libur berhasil diambil.',
                'data' => $holidays,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($holiday) {
            return $this->formatHoliday($holiday);
        });

        return $this->paginatedResponse($paginated, 'Daftar hari libur berhasil diambil.');
    }

    /**
     * Store a newly created holiday.
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

        // Support both 'name' and 'title' from request
        $name = $request->input('name', $request->input('title'));
        $request->merge(['name' => $name]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable',
            'division_id' => 'nullable',
        ], [
            'name.required' => 'Nama atau judul hari libur wajib diisi.',
            'start_date.required' => 'Tanggal mulai libur wajib diisi.',
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.required' => 'Tanggal selesai libur wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
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

        $holiday = Holiday::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'name' => $request->name,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
        ]);

        $holiday->load(['branch:id,name', 'division:id,name']);

        return response()->json([
            'status' => true,
            'message' => 'Hari libur berhasil ditambahkan.',
            'data' => $this->formatHoliday($holiday),
        ], 201);
    }

    /**
     * Get detail of a specific holiday.
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

        $holiday = Holiday::with(['branch:id,name', 'division:id,name'])
            ->where('company_id', $company->id)
            ->find($id);

        if (!$holiday) {
            return response()->json([
                'status' => false,
                'message' => 'Data hari libur tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail hari libur berhasil diambil.',
            'data' => $this->formatHoliday($holiday),
        ]);
    }

    /**
     * Update an existing holiday.
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

        $holiday = Holiday::where('company_id', $company->id)->find($id);
        if (!$holiday) {
            return response()->json([
                'status' => false,
                'message' => 'Data hari libur tidak ditemukan.',
            ], 404);
        }

        // Support both 'name' and 'title' from request
        $name = $request->input('name', $request->input('title', $holiday->name));
        $request->merge(['name' => $name]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable',
            'division_id' => 'nullable',
        ], [
            'name.required' => 'Nama atau judul hari libur wajib diisi.',
            'start_date.required' => 'Tanggal mulai libur wajib diisi.',
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.required' => 'Tanggal selesai libur wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branchId = $this->parseNullableId($request->input('branch_id', $request->input('branch', $holiday->branch_id)));
        $divisionId = $this->parseNullableId($request->input('division_id', $request->input('division', $holiday->division_id)));

        $holiday->update([
            'branch_id' => $branchId,
            'division_id' => $divisionId,
            'name' => $request->name,
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
        ]);

        $holiday->load(['branch:id,name', 'division:id,name']);

        return response()->json([
            'status' => true,
            'message' => 'Data hari libur berhasil diperbarui.',
            'data' => $this->formatHoliday($holiday),
        ]);
    }

    /**
     * Delete an existing holiday.
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

        $holiday = Holiday::where('company_id', $company->id)->find($id);
        if (!$holiday) {
            return response()->json([
                'status' => false,
                'message' => 'Data hari libur tidak ditemukan.',
            ], 404);
        }

        $holiday->delete();

        return response()->json([
            'status' => true,
            'message' => 'Hari libur berhasil dihapus.',
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
     * Format holiday array response for Flutter & frontend consistency.
     */
    private function formatHoliday(Holiday $holiday): array
    {
        $start = Carbon::parse($holiday->start_date);
        $end = Carbon::parse($holiday->end_date);
        $duration = $start->diffInDays($end) + 1;

        $dateFormatted = $start->isSameDay($end)
            ? $start->isoFormat('D MMMM Y')
            : $start->isoFormat('D MMMM') . ' - ' . $end->isoFormat('D MMMM Y');

        return [
            'id' => $holiday->id,
            'company_id' => $holiday->company_id,
            'name' => $holiday->name,
            'title' => $holiday->name, // alias for consistency
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'duration_days' => $duration,
            'date_formatted' => $dateFormatted,
            'branch_id' => $holiday->branch_id,
            'branch_name' => $holiday->branch ? $holiday->branch->name : 'Semua Cabang',
            'division_id' => $holiday->division_id,
            'division_name' => $holiday->division ? $holiday->division->name : 'Semua Divisi',
            'branch' => $holiday->branch ? [
                'id' => $holiday->branch->id,
                'name' => $holiday->branch->name,
            ] : null,
            'division' => $holiday->division ? [
                'id' => $holiday->division->id,
                'name' => $holiday->division->name,
            ] : null,
            'created_at' => $holiday->created_at ? Carbon::parse($holiday->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $holiday->updated_at ? Carbon::parse($holiday->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
