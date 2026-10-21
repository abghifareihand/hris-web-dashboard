<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    /**
     * Get list of shifts (paginated or all).
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

        $query = Shift::where('company_id', $company->id)->withCount('workSchedules');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('name', 'asc');

        // Unpaginated list (for dropdowns / schedule pickers)
        if ($request->boolean('all')) {
            $shifts = $query->get()->map(function ($shift) {
                return $this->formatShift($shift);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar semua shift kerja berhasil diambil.',
                'data' => $shifts,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($shift) {
            return $this->formatShift($shift);
        });

        return $this->paginatedResponse($paginated, 'Daftar shift kerja berhasil diambil.');
    }

    /**
     * Store a newly created shift.
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
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
        ], [
            'name.required' => 'Nama shift wajib diisi.',
            'clock_in.required' => 'Jam masuk kerja wajib diisi.',
            'clock_in.date_format' => 'Format jam masuk harus HH:mm (contoh 08:00).',
            'clock_out.required' => 'Jam pulang kerja wajib diisi.',
            'clock_out.date_format' => 'Format jam pulang harus HH:mm (contoh 17:00).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shift = Shift::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        $shift->loadCount('workSchedules');

        return response()->json([
            'status' => true,
            'message' => 'Shift kerja baru berhasil ditambahkan.',
            'data' => $this->formatShift($shift),
        ], 201);
    }

    /**
     * Get detail of a specific shift.
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

        $shift = Shift::where('company_id', $company->id)
            ->withCount('workSchedules')
            ->find($id);

        if (!$shift) {
            return response()->json([
                'status' => false,
                'message' => 'Shift kerja tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail shift kerja berhasil diambil.',
            'data' => $this->formatShift($shift),
        ]);
    }

    /**
     * Update an existing shift.
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

        $shift = Shift::where('company_id', $company->id)->find($id);
        if (!$shift) {
            return response()->json([
                'status' => false,
                'message' => 'Shift kerja tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
        ], [
            'name.required' => 'Nama shift wajib diisi.',
            'clock_in.required' => 'Jam masuk kerja wajib diisi.',
            'clock_in.date_format' => 'Format jam masuk harus HH:mm (contoh 08:00).',
            'clock_out.required' => 'Jam pulang kerja wajib diisi.',
            'clock_out.date_format' => 'Format jam pulang harus HH:mm (contoh 17:00).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shift->update([
            'name' => $request->name,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Shift kerja berhasil diperbarui.',
        ]);
    }

    /**
     * Delete an existing shift.
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

        $shift = Shift::where('company_id', $company->id)->find($id);
        if (!$shift) {
            return response()->json([
                'status' => false,
                'message' => 'Shift kerja tidak ditemukan.',
            ], 404);
        }

        if ($shift->workSchedules()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus shift kerja karena sedang digunakan dalam jadwal kerja karyawan.',
            ], 422);
        }

        $shift->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shift kerja berhasil dihapus.',
        ]);
    }

    /**
     * Format shift model array.
     */
    private function formatShift(Shift $shift): array
    {
        $clockIn = $shift->clock_in ? substr($shift->clock_in, 0, 5) : '';
        $clockOut = $shift->clock_out ? substr($shift->clock_out, 0, 5) : '';

        return [
            'id' => $shift->id,
            'company_id' => $shift->company_id,
            'name' => $shift->name,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_raw' => $shift->clock_in,
            'clock_out_raw' => $shift->clock_out,
            'time_formatted' => "{$clockIn} - {$clockOut}",
            'schedules_count' => (int) ($shift->work_schedules_count ?? $shift->workSchedules()->count()),
            'created_at' => $shift->created_at ? Carbon::parse($shift->created_at)->format('Y-m-d H:i') : null,
            'updated_at' => $shift->updated_at ? Carbon::parse($shift->updated_at)->format('Y-m-d H:i') : null,
        ];
    }
}
