<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveCategory;
use App\Models\PendingEmployee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PendingEmployeeController extends Controller
{
    /**
     * Get list of pending employee registrations for the owner's company.
     *
     * Query Parameters:
     * - search: search by name, email, or phone
     * - branch_id: filter by branch
     * - division_id: filter by division
     * - position_id: filter by position
     * - status: 'pending' (default), 'approved', 'rejected', or 'all'
     * - per_page: integer (default: 15)
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $query = PendingEmployee::with([
            'branch:id,name',
            'division:id,name',
            'position:id,name'
        ])->where('company_id', $company->id);

        // Status filter (default: 'pending')
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        $perPage = $this->getPerPage($request);
        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->formatPendingEmployee($item);
        });

        return $this->paginatedResponse($paginated, 'Daftar calon karyawan berhasil diambil.');
    }

    /**
     * Get detail of a single pending employee registration.
     */
    public function show(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $pending = PendingEmployee::with([
            'branch:id,name',
            'division:id,name',
            'position:id,name'
        ])->where('company_id', $company->id)->find($id);

        if (!$pending) {
            return response()->json([
                'status'  => false,
                'message' => 'Data pendaftaran calon karyawan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail calon karyawan berhasil diambil.',
            'data'    => $this->formatPendingEmployee($pending),
        ]);
    }

    /**
     * Approve pending employee registration and create official User + Employee record.
     */
    public function approve(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $pending = PendingEmployee::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$pending) {
            return response()->json([
                'status'  => false,
                'message' => 'Data calon karyawan berstatus pending tidak ditemukan.'
            ], 404);
        }

        // Validate optional overrides if provided in request
        $validator = Validator::make($request->all(), [
            'nip'          => ['nullable', 'string', 'max:50', Rule::unique('employees', 'nip')->where('company_id', $company->id)],
            'nik'          => ['nullable', 'string', 'max:50'],
            'gender'       => ['nullable', 'in:male,female'],
            'joined_at'    => ['nullable', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'division_id'  => ['nullable', 'exists:divisions,id'],
            'position_id'  => ['nullable', 'exists:positions,id'],
        ], [
            'nip.unique'          => 'NIP sudah digunakan oleh karyawan lain.',
            'branch_id.exists'    => 'Cabang yang dipilih tidak valid.',
            'division_id.exists'  => 'Divisi yang dipilih tidak valid.',
            'position_id.exists'  => 'Jabatan yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Prevent duplicate user/employee creation if email already registered
        if (User::where('email', $pending->email)->exists() || Employee::where('email', $pending->email)->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Email pendaftar (' . $pending->email . ') sudah terdaftar di sistem sebagai karyawan aktif.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Create official User account
            $user = User::create([
                'name'              => $pending->name,
                'email'             => $pending->email,
                'password'          => $pending->password, // Already hashed bcrypt
                'role'              => 'employee',
                'email_verified_at' => now(),
            ]);

            // 2. Create Employee profile
            $branchId = $request->input('branch_id', $pending->branch_id);
            $divisionId = $request->input('division_id', $pending->division_id);
            $positionId = $request->input('position_id', $pending->position_id);

            $employee = Employee::create([
                'user_id'      => $user->id,
                'company_id'   => $company->id,
                'branch_id'    => $branchId,
                'division_id'  => $divisionId,
                'position_id'  => $positionId,
                'name'         => $pending->name,
                'email'        => $pending->email,
                'phone'        => $pending->phone,
                'address'      => $pending->address,
                'nip'          => $request->input('nip'),
                'nik'          => $request->input('nik'),
                'gender'       => $request->input('gender'),
                'joined_at'    => $request->input('joined_at', now()->format('Y-m-d')),
                'basic_salary' => $request->input('basic_salary', 0),
                'is_active'    => true,
            ]);

            // 3. Auto-generate leave balances for the new employee
            $categories = LeaveCategory::where('company_id', $company->id)->get();
            $currentYear = (int) date('Y');
            foreach ($categories as $cat) {
                LeaveBalance::firstOrCreate([
                    'company_id'        => $company->id,
                    'employee_id'       => $employee->id,
                    'leave_category_id' => $cat->id,
                    'year'              => $currentYear,
                ], [
                    'quota' => $cat->default_quota,
                    'used'  => 0,
                ]);
            }

            // 4. Update pending status to approved
            $pending->update([
                'status' => 'approved',
            ]);

            DB::commit();

            $employee->load([
                'branch:id,name',
                'division:id,name',
                'position:id,name',
                'user:id,name,email,avatar'
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Calon karyawan berhasil disetujui dan ditambahkan ke daftar karyawan.',
                'data'    => [
                    'pending_id'  => $pending->id,
                    'employee_id' => $employee->id,
                    'user_id'     => $user->id,
                    'name'        => $employee->name,
                    'email'       => $employee->email,
                    'nip'         => $employee->nip,
                    'phone'       => $employee->phone,
                    'branch'      => $employee->branch ? ['id' => $employee->branch->id, 'name' => $employee->branch->name] : null,
                    'division'    => $employee->division ? ['id' => $employee->division->id, 'name' => $employee->division->name] : null,
                    'position'    => $employee->position ? ['id' => $employee->position->id, 'name' => $employee->position->name] : null,
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan saat memproses persetujuan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject pending employee registration.
     */
    public function reject(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $pending = PendingEmployee::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$pending) {
            return response()->json([
                'status'  => false,
                'message' => 'Data calon karyawan berstatus pending tidak ditemukan.'
            ], 404);
        }

        $pending->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Pendaftaran calon karyawan telah ditolak.',
            'data'    => [
                'id'     => $pending->id,
                'status' => 'rejected'
            ]
        ]);
    }

    /**
     * Helper to format PendingEmployee model to consistent JSON.
     */
    protected function formatPendingEmployee(PendingEmployee $item): array
    {
        $createdAt = Carbon::parse($item->created_at);

        return [
            'id'                   => $item->id,
            'company_id'           => $item->company_id,
            'name'                 => $item->name,
            'email'                => $item->email,
            'phone'                => $item->phone,
            'address'              => $item->address,
            'status'               => $item->status,
            'branch'               => $item->branch ? [
                'id'   => $item->branch->id,
                'name' => $item->branch->name,
            ] : null,
            'division'             => $item->division ? [
                'id'   => $item->division->id,
                'name' => $item->division->name,
            ] : null,
            'position'             => $item->position ? [
                'id'   => $item->position->id,
                'name' => $item->position->name,
            ] : null,
            'created_at'           => $createdAt->toIso8601String(),
            'created_at_formatted' => $createdAt->translatedFormat('d F Y, H:i'),
            'time_ago'             => $createdAt->diffForHumans(),
        ];
    }
}
