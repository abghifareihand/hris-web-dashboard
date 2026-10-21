<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveCategory;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Get employees for the owner's company.
     * Features search, branch, division, position, active status filtering, and pagination.
     */
    public function getEmployees(Request $request)
    {
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $query = Employee::with([
            'position:id,name',
            'division:id,name',
            'branch:id,name',
            'user:id,name,email,avatar'
        ])->where('company_id', $company->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
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

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $this->getPerPage($request);
        $employees = $query->orderBy('name', 'asc')->paginate($perPage);

        // Format lightweight card data for employee list
        $employees->getCollection()->transform(function ($emp) {
            return [
                'id' => $emp->id,
                'user_id' => $emp->user_id,
                'name' => $emp->name,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'nip' => $emp->nip,
                'gender' => $emp->gender,
                'is_active' => (bool) $emp->is_active,
                'avatar_url' => ($emp->user && $emp->user->avatar)
                    ? asset('storage/' . $emp->user->avatar)
                    : null,
                'branch_id' => $emp->branch_id,
                'branch' => $emp->branch ? [
                    'id' => $emp->branch->id,
                    'name' => $emp->branch->name,
                ] : null,
                'division_id' => $emp->division_id,
                'division' => $emp->division ? [
                    'id' => $emp->division->id,
                    'name' => $emp->division->name,
                ] : null,
                'position_id' => $emp->position_id,
                'position' => $emp->position ? [
                    'id' => $emp->position->id,
                    'name' => $emp->position->name,
                ] : null,
                'joined_at' => $emp->joined_at ? Carbon::parse($emp->joined_at)->format('Y-m-d') : null,
                'created_at' => $emp->created_at ? Carbon::parse($emp->created_at)->format('Y-m-d H:i') : null,
                'updated_at' => $emp->updated_at ? Carbon::parse($emp->updated_at)->format('Y-m-d H:i') : null,
            ];
        });

        return $this->paginatedResponse($employees, 'Data karyawan berhasil diambil.');
    }

    /**
     * Get detailed profile of a specific employee.
     */
    public function show(Request $request, $id)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employee = Employee::with([
            'branch:id,name',
            'division:id,name',
            'position:id,name',
            'user:id,name,email,avatar',
        ])
        ->where('company_id', $company->id)
        ->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $avatarUrl = ($employee->user && $employee->user->avatar) 
            ? asset('storage/' . $employee->user->avatar) 
            : null;

        // PTKP extraction helper
        $ptkp = $employee->ptkp_status ?? 'TK/0';
        $maritalStatus = 'single';
        if (str_starts_with($ptkp, 'K/I/')) {
            $maritalStatus = 'married_merged';
        } elseif (str_starts_with($ptkp, 'K/')) {
            $maritalStatus = 'married';
        }
        $parts = explode('/', $ptkp);
        $dependents = (int) end($parts);

        return response()->json([
            'status' => true,
            'message' => 'Detail karyawan berhasil diambil.',
            'data' => [
                'id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'gender' => $employee->gender,
                'nik' => $employee->nik,
                'nip' => $employee->nip,
                'joined_at' => $employee->joined_at ? Carbon::parse($employee->joined_at)->format('Y-m-d') : null,
                'birth_date' => $employee->birth_date ? Carbon::parse($employee->birth_date)->format('Y-m-d') : null,
                'address' => $employee->address,
                'is_active' => (bool) $employee->is_active,
                'avatar_url' => $avatarUrl,

                // Organization
                'branch_id' => $employee->branch_id,
                'branch' => $employee->branch ? [
                    'id' => $employee->branch->id,
                    'name' => $employee->branch->name,
                ] : null,
                'division_id' => $employee->division_id,
                'division' => $employee->division ? [
                    'id' => $employee->division->id,
                    'name' => $employee->division->name,
                ] : null,
                'position_id' => $employee->position_id,
                'position' => $employee->position ? [
                    'id' => $employee->position->id,
                    'name' => $employee->position->name,
                ] : null,

                // Bank Info
                'bank_name' => $employee->bank_name,
                'bank_account_number' => $employee->bank_account_number,
                'bank_account_name' => $employee->bank_account_name,

                // Payroll & Allowances
                'payroll_cycle' => $employee->payroll_cycle ?? 'monthly',
                'basic_salary' => (int) $employee->basic_salary,
                'fixed_allowance' => (int) $employee->fixed_allowance,
                'daily_allowance' => (int) $employee->daily_allowance,
                'other_allowance' => (int) $employee->other_allowance,
                'overtime_rate_per_hour' => (int) $employee->overtime_rate_per_hour,

                // Penalties
                'late_penalty_type' => $employee->late_penalty_type ?? 'prorate',
                'late_penalty_nominal' => (int) $employee->late_penalty_nominal,
                'alpha_penalty_type' => $employee->alpha_penalty_type ?? 'prorate',
                'alpha_penalty_nominal' => (int) $employee->alpha_penalty_nominal,

                // Tax & BPJS
                'is_taxable' => (bool) $employee->taxable,
                'npwp_number' => $employee->npwp,
                'ptkp_status' => $employee->ptkp_status,
                'marital_status' => $maritalStatus,
                'dependents' => $dependents,
                'bpjs_kesehatan_number' => $employee->bpjs_kesehatan_no,
                'bpjs_ketenagakerjaan_number' => $employee->jht_no,

                'created_at' => $employee->created_at ? Carbon::parse($employee->created_at)->format('Y-m-d H:i') : null,
                'updated_at' => $employee->updated_at ? Carbon::parse($employee->updated_at)->format('Y-m-d H:i') : null,
            ]
        ]);
    }

    /**
     * Create a new employee for the owner's company.
     */
    public function store(Request $request)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],

            // Optional extra fields
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'nik' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'joined_at' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],

            // Bank details
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],

            // Salary & Payroll
            'payroll_cycle' => ['nullable', 'in:monthly,weekly,daily'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'fixed_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0'],

            // Penalties
            'late_penalty_type' => ['nullable', 'in:prorate,flat'],
            'late_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'alpha_penalty_type' => ['nullable', 'in:prorate,flat'],
            'alpha_penalty_nominal' => ['nullable', 'numeric', 'min:0'],

            // Tax & BPJS
            'is_taxable' => ['nullable', 'boolean'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'in:single,married,married_merged'],
            'dependents' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama karyawan wajib diisi.',
            'email.required' => 'Email karyawan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar di sistem.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Kata sandi akun wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'branch_id.exists' => 'Cabang yang dipilih tidak valid.',
            'division_id.exists' => 'Divisi yang dipilih tidak valid.',
            'position_id.exists' => 'Jabatan yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $newEmployee = DB::transaction(function () use ($request, $company) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'employee',
                'avatar' => $avatarPath,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'gender' => $request->gender,
                'nik' => $request->nik,
                'nip' => $request->nip,
                'joined_at' => $request->joined_at ?? now(),
                'birth_date' => $request->birth_date,
                'is_active' => true,

                // Bank details
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_name' => $request->bank_account_name,

                // Salary & Allowances
                'payroll_cycle' => $request->input('payroll_cycle', 'monthly'),
                'basic_salary' => $request->input('basic_salary', 0),
                'fixed_allowance' => $request->input('fixed_allowance', 0),
                'daily_allowance' => $request->input('daily_allowance', 0),
                'other_allowance' => $request->input('other_allowance', 0),
                'overtime_rate_per_hour' => $request->input('overtime_rate_per_hour', 0),

                // Penalties
                'late_penalty_type' => $request->input('late_penalty_type', 'prorate'),
                'late_penalty_nominal' => $request->input('late_penalty_nominal', 0),
                'alpha_penalty_type' => $request->input('alpha_penalty_type', 'prorate'),
                'alpha_penalty_nominal' => $request->input('alpha_penalty_nominal', 0),

                // Tax & BPJS
                'taxable' => $request->has('is_taxable') ? $request->boolean('is_taxable') : true,
                'npwp' => $request->npwp_number,
                'ptkp_status' => (match ($request->marital_status) {
                    'married' => 'K/',
                    'married_merged' => 'K/I/',
                    default => 'TK/',
                }) . min(max((int)$request->input('dependents', 0), 0), 3),
                'bpjs_kesehatan_no' => $request->bpjs_kesehatan_number,
                'jht_no' => $request->bpjs_ketenagakerjaan_number,
                'jp_no' => $request->bpjs_ketenagakerjaan_number,
                'jkk_no' => $request->bpjs_ketenagakerjaan_number,
                'jkm_no' => $request->bpjs_ketenagakerjaan_number,
            ]);

            // Auto-generate leave balances for the new employee
            $categories = LeaveCategory::where('company_id', $company->id)->get();
            $currentYear = (int) date('Y');
            foreach ($categories as $cat) {
                LeaveBalance::firstOrCreate([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'leave_category_id' => $cat->id,
                    'year' => $currentYear,
                ], [
                    'quota' => $cat->default_quota,
                    'used' => 0,
                ]);
            }

            return $employee;
        });

        return response()->json([
            'status' => true,
            'message' => 'Karyawan baru berhasil ditambahkan.',
        ], 201);
    }

    /**
     * Update an existing employee's data.
     */
    public function update(Request $request, $id)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employee = Employee::where('company_id', $company->id)->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $user = $employee->user;

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'nik' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'joined_at' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delete_avatar' => ['nullable', 'boolean'],

            // Bank details
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],

            // Salary & Allowances
            'payroll_cycle' => ['nullable', 'in:monthly,weekly,daily'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'fixed_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0'],

            // Penalties
            'late_penalty_type' => ['nullable', 'in:prorate,flat'],
            'late_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'alpha_penalty_type' => ['nullable', 'in:prorate,flat'],
            'alpha_penalty_nominal' => ['nullable', 'numeric', 'min:0'],

            // Tax & BPJS
            'is_taxable' => ['nullable', 'boolean'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'in:single,married,married_merged'],
            'dependents' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama karyawan wajib diisi.',
            'email.required' => 'Email karyawan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar di sistem.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'branch_id.exists' => 'Cabang yang dipilih tidak valid.',
            'division_id.exists' => 'Divisi yang dipilih tidak valid.',
            'position_id.exists' => 'Jabatan yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $employee, $user) {
            // Update User
            if ($user) {
                $userData = [];
                if ($request->has('name')) {
                    $userData['name'] = $request->name;
                }
                if ($request->has('email')) {
                    $userData['email'] = $request->email;
                }
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }
                if ($request->hasFile('avatar')) {
                    if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                    $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
                } elseif ($request->boolean('delete_avatar')) {
                    if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                    $userData['avatar'] = null;
                }
                if (!empty($userData)) {
                    $user->update($userData);
                }
            }

            // Update Employee
            $empData = [];
            $fillable = [
                'name', 'email', 'phone', 'gender', 'nik', 'nip',
                'branch_id', 'division_id', 'position_id', 'joined_at',
                'birth_date', 'address', 'bank_name', 'bank_account_number',
                'bank_account_name', 'payroll_cycle', 'basic_salary',
                'fixed_allowance', 'daily_allowance', 'other_allowance',
                'overtime_rate_per_hour', 'late_penalty_type', 'late_penalty_nominal',
                'alpha_penalty_type', 'alpha_penalty_nominal'
            ];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $empData[$field] = $request->input($field);
                }
            }

            if ($request->has('is_active')) {
                $empData['is_active'] = $request->boolean('is_active');
            }
            if ($request->has('is_taxable')) {
                $empData['taxable'] = $request->boolean('is_taxable');
            }
            if ($request->has('npwp_number')) {
                $empData['npwp'] = $request->npwp_number;
            }
            if ($request->has('marital_status') || $request->has('dependents')) {
                $marital = $request->input('marital_status', 'single');
                $deps = min(max((int)$request->input('dependents', 0), 0), 3);
                $empData['ptkp_status'] = (match ($marital) {
                    'married' => 'K/',
                    'married_merged' => 'K/I/',
                    default => 'TK/',
                }) . $deps;
            }
            if ($request->has('bpjs_kesehatan_number')) {
                $empData['bpjs_kesehatan_no'] = $request->bpjs_kesehatan_number;
            }
            if ($request->has('bpjs_ketenagakerjaan_number')) {
                $val = $request->bpjs_ketenagakerjaan_number;
                $empData['jht_no'] = $val;
                $empData['jp_no'] = $val;
                $empData['jkk_no'] = $val;
                $empData['jkm_no'] = $val;
            }

            if (!empty($empData)) {
                $employee->update($empData);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Data karyawan berhasil diperbarui.',
        ]);
    }

    /**
     * Activate or deactivate an employee.
     */
    public function toggleStatus(Request $request, $id)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employee = Employee::where('company_id', $company->id)->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $newStatus = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : !$employee->is_active;

        $employee->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'status' => true,
            'message' => "Karyawan berhasil {$statusText}.",
            'data' => [
                'id' => $employee->id,
                'is_active' => (bool) $employee->is_active,
            ]
        ]);
    }

    /**
     * Delete an employee.
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employee = Employee::where('company_id', $company->id)->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                if ($employee->user->avatar && Storage::disk('public')->exists($employee->user->avatar)) {
                    Storage::disk('public')->delete($employee->user->avatar);
                }
                $employee->user->delete();
            } else {
                $employee->delete();
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Karyawan berhasil dihapus.'
        ]);
    }

    /**
     * Get company structure (branches, divisions & positions) for the owner's company
     * Used for dropdown filters and creation forms in the app.
     */
    public function getCompanyStructure(Request $request)
    {
        $company = $request->user()->company;
        
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $company->load([
            'branches:id,company_id,name',
            'divisions:id,company_id,name',
            'positions:id,company_id,name',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Struktur perusahaan berhasil diambil.',
            'data' => [
                'branches' => $company->branches,
                'divisions' => $company->divisions,
                'positions' => $company->positions,
            ]
        ]);
    }
}
