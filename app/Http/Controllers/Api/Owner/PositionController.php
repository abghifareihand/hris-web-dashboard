<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    /**
     * Get list of positions (paginated or all).
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

        $query = Position::where('company_id', $company->id)
            ->with(['branches:id,name', 'divisions:id,name'])
            ->withCount('employees');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        if ($request->filled('division_id')) {
            $divisionId = $request->division_id;
            $query->whereHas('divisions', function ($q) use ($divisionId) {
                $q->where('divisions.id', $divisionId);
            });
        }

        $query->orderBy('name', 'asc');

        // Unpaginated list for dropdowns
        if ($request->boolean('all')) {
            $positions = $query->get()->map(function ($pos) {
                return $this->formatPosition($pos);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua jabatan berhasil diambil.',
                'data' => $positions,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($pos) {
            return $this->formatPosition($pos);
        });

        return $this->paginatedResponse($paginated, 'Daftar jabatan berhasil diambil.');
    }

    /**
     * Store a newly created position.
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
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
            'divisions' => 'nullable|array',
            'divisions.*' => 'exists:divisions,id',
        ], [
            'name.required' => 'Nama jabatan wajib diisi.',
            'branches.*.exists' => 'Cabang yang dipilih tidak valid.',
            'divisions.*.exists' => 'Divisi yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $position = Position::create([
            'company_id' => $company->id,
            'name' => $request->name,
        ]);

        if ($request->filled('branches')) {
            $position->branches()->sync($request->branches);
        }

        if ($request->filled('divisions')) {
            $position->divisions()->sync($request->divisions);
        }

        $position->load(['branches:id,name', 'divisions:id,name'])->loadCount('employees');

        return response()->json([
            'status' => true,
            'message' => 'Jabatan baru berhasil ditambahkan.',
            'data' => $this->formatPosition($position),
        ], 201);
    }

    /**
     * Get detail of a specific position.
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

        $position = Position::where('company_id', $company->id)
            ->with(['branches:id,name', 'divisions:id,name'])
            ->withCount('employees')
            ->find($id);

        if (!$position) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail jabatan berhasil diambil.',
            'data' => $this->formatPosition($position),
        ]);
    }

    /**
     * Update an existing position.
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

        $position = Position::where('company_id', $company->id)->find($id);
        if (!$position) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
            'divisions' => 'nullable|array',
            'divisions.*' => 'exists:divisions,id',
        ], [
            'name.required' => 'Nama jabatan wajib diisi.',
            'branches.*.exists' => 'Cabang yang dipilih tidak valid.',
            'divisions.*.exists' => 'Divisi yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $position->update([
            'name' => $request->name,
        ]);

        if ($request->has('branches')) {
            $position->branches()->sync($request->branches ?? []);
        }

        if ($request->has('divisions')) {
            $position->divisions()->sync($request->divisions ?? []);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data jabatan berhasil diperbarui.',
        ]);
    }

    /**
     * Delete an existing position.
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

        $position = Position::where('company_id', $company->id)->find($id);
        if (!$position) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan tidak ditemukan.',
            ], 404);
        }

        if ($position->employees()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus jabatan karena masih digunakan oleh karyawan yang terdaftar.',
            ], 422);
        }

        $position->branches()->detach();
        $position->divisions()->detach();
        $position->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan berhasil dihapus.',
        ]);
    }

    /**
     * Format position model array.
     */
    private function formatPosition(Position $position): array
    {
        return [
            'id' => $position->id,
            'company_id' => $position->company_id,
            'name' => $position->name,
            'employees_count' => (int) ($position->employees_count ?? $position->employees()->count()),
            'branches' => $position->branches ? $position->branches->map(function ($b) {
                return ['id' => $b->id, 'name' => $b->name];
            })->values()->all() : [],
            'branch_ids' => $position->branches ? $position->branches->pluck('id')->all() : [],
            'divisions' => $position->divisions ? $position->divisions->map(function ($d) {
                return ['id' => $d->id, 'name' => $d->name];
            })->values()->all() : [],
            'division_ids' => $position->divisions ? $position->divisions->pluck('id')->all() : [],
            'created_at' => $position->created_at ? Carbon::parse($position->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $position->updated_at ? Carbon::parse($position->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
