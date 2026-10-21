<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    /**
     * Get list of branches (paginated or all).
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

        $query = Branch::where('company_id', $company->id)->withCount('employees');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('timezone')) {
            $query->where('timezone', $request->timezone);
        }

        $query->orderBy('name', 'asc');

        // Support unpaginated retrieval for dropdowns
        if ($request->boolean('all')) {
            $branches = $query->get()->map(function ($branch) {
                return $this->formatBranch($branch);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua cabang berhasil diambil.',
                'data' => $branches,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($branch) {
            return $this->formatBranch($branch);
        });

        return $this->paginatedResponse($paginated, 'Daftar cabang berhasil diambil.');
    }

    /**
     * Store a newly created branch.
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id);
                }),
            ],
            'timezone' => 'required|string|in:WIB,WITA,WIT',
            'radius' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string|max:500',
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'code.required' => 'Kode cabang wajib diisi.',
            'code.unique' => 'Kode cabang sudah digunakan di perusahaan Anda.',
            'timezone.required' => 'Zona waktu wajib dipilih.',
            'timezone.in' => 'Zona waktu harus berupa WIB, WITA, atau WIT.',
            'radius.required' => 'Radius presensi wajib diisi.',
            'radius.integer' => 'Radius presensi harus berupa angka.',
            'radius.min' => 'Radius presensi minimal 1 meter.',
            'latitude.required' => 'Titik koordinat latitude wajib diisi.',
            'longitude.required' => 'Titik koordinat longitude wajib diisi.',
            'address.required' => 'Alamat lengkap cabang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'timezone' => $request->timezone,
            'radius' => (int) $request->radius,
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cabang baru berhasil ditambahkan.',
            'data' => $this->formatBranch($branch),
        ], 201);
    }

    /**
     * Get detail of a specific branch.
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

        $branch = Branch::where('company_id', $company->id)
            ->withCount('employees')
            ->find($id);

        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail cabang berhasil diambil.',
            'data' => $this->formatBranch($branch),
        ]);
    }

    /**
     * Update an existing branch.
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

        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id);
                })->ignore($branch->id),
            ],
            'timezone' => 'required|string|in:WIB,WITA,WIT',
            'radius' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string|max:500',
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'code.required' => 'Kode cabang wajib diisi.',
            'code.unique' => 'Kode cabang sudah digunakan di perusahaan Anda.',
            'timezone.required' => 'Zona waktu wajib dipilih.',
            'timezone.in' => 'Zona waktu harus berupa WIB, WITA, atau WIT.',
            'radius.required' => 'Radius presensi wajib diisi.',
            'latitude.required' => 'Titik koordinat latitude wajib diisi.',
            'longitude.required' => 'Titik koordinat longitude wajib diisi.',
            'address.required' => 'Alamat lengkap cabang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'timezone' => $request->timezone,
            'radius' => (int) $request->radius,
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data cabang berhasil diperbarui.',
        ]);
    }

    /**
     * Delete an existing branch.
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

        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        if ($branch->employees()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus cabang karena masih ada karyawan yang terdaftar di cabang ini.',
            ], 422);
        }

        // Detach positions pivot relation
        $branch->positions()->detach();
        $branch->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cabang berhasil dihapus.',
        ]);
    }

    /**
     * Format branch model array.
     */
    private function formatBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'company_id' => $branch->company_id,
            'name' => $branch->name,
            'code' => $branch->code,
            'timezone' => $branch->timezone,
            'radius' => (int) $branch->radius,
            'latitude' => (float) $branch->latitude,
            'longitude' => (float) $branch->longitude,
            'address' => $branch->address,
            'employees_count' => (int) ($branch->employees_count ?? $branch->employees()->count()),
            'created_at' => $branch->created_at ? Carbon::parse($branch->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $branch->updated_at ? Carbon::parse($branch->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
