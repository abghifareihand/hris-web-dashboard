<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DivisionController extends Controller
{
    /**
     * Get list of divisions (paginated or all).
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

        $query = Division::where('company_id', $company->id)->withCount('employees');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_attendance_schedule')) {
            $query->where('is_attendance_schedule', $request->boolean('is_attendance_schedule'));
        }

        if ($request->filled('is_attendance_radius')) {
            $query->where('is_attendance_radius', $request->boolean('is_attendance_radius'));
        }

        $query->orderBy('name', 'asc');

        // Unpaginated list for dropdowns
        if ($request->boolean('all')) {
            $divisions = $query->get()->map(function ($div) {
                return $this->formatDivision($div);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua divisi berhasil diambil.',
                'data' => $divisions,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($div) {
            return $this->formatDivision($div);
        });

        return $this->paginatedResponse($paginated, 'Daftar divisi berhasil diambil.');
    }

    /**
     * Store a newly created division.
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
            'name' => 'required|string|max:255',
            'is_attendance_schedule' => 'nullable|boolean',
            'is_attendance_radius' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama divisi wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $division = Division::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'is_attendance_schedule' => $request->boolean('is_attendance_schedule'),
            'is_attendance_radius' => $request->boolean('is_attendance_radius'),
        ]);

        $division->loadCount('employees');

        return response()->json([
            'status' => true,
            'message' => 'Divisi baru berhasil ditambahkan.',
            'data' => $this->formatDivision($division),
        ], 201);
    }

    /**
     * Get detail of a specific division.
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

        $division = Division::where('company_id', $company->id)
            ->withCount('employees')
            ->find($id);

        if (!$division) {
            return response()->json([
                'status' => false,
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail divisi berhasil diambil.',
            'data' => $this->formatDivision($division),
        ]);
    }

    /**
     * Update an existing division.
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

        $division = Division::where('company_id', $company->id)->find($id);
        if (!$division) {
            return response()->json([
                'status' => false,
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_attendance_schedule' => 'nullable|boolean',
            'is_attendance_radius' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama divisi wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $division->update([
            'name' => $request->name,
            'is_attendance_schedule' => $request->boolean('is_attendance_schedule'),
            'is_attendance_radius' => $request->boolean('is_attendance_radius'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data divisi berhasil diperbarui.',
        ]);
    }

    /**
     * Delete an existing division.
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

        $division = Division::where('company_id', $company->id)->find($id);
        if (!$division) {
            return response()->json([
                'status' => false,
                'message' => 'Divisi tidak ditemukan.',
            ], 404);
        }

        if ($division->employees()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus divisi karena masih memiliki karyawan yang terdaftar.',
            ], 422);
        }

        $division->positions()->detach();
        $division->delete();

        return response()->json([
            'status' => true,
            'message' => 'Divisi berhasil dihapus.',
        ]);
    }

    /**
     * Format division model array.
     */
    private function formatDivision(Division $division): array
    {
        return [
            'id' => $division->id,
            'company_id' => $division->company_id,
            'name' => $division->name,
            'is_attendance_schedule' => (bool) $division->is_attendance_schedule,
            'is_attendance_radius' => (bool) $division->is_attendance_radius,
            'employees_count' => (int) ($division->employees_count ?? $division->employees()->count()),
            'created_at' => $division->created_at ? Carbon::parse($division->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $division->updated_at ? Carbon::parse($division->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
