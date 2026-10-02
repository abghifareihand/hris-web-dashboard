<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $query = Employee::where('company_id', $company->id)
            ->with([
                'branch:id,name',
                'division:id,name',
                'position:id,name',
                'user:id,name,email,avatar',
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->input('division_id'));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        $employees = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = $company->branches()->orderBy('name')->get(['id', 'name']);
        $divisions = $company->divisions()->orderBy('name')->get(['id', 'name']);
        $positions = $company->positions()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Employees/Index', [
            'employees' => $employees,
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
            'filters' => [
                'search' => $request->search,
                'is_active' => $request->is_active,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'position_id' => $request->position_id,
            ],
        ]);
    }

    public function create()
    {
        $company = auth()->user()->company;
        $branches = $company->branches()->orderBy('name')->get(['id', 'name']);
        $divisions = $company->divisions()->orderBy('name')->get(['id', 'name']);
        $positions = $company->positions()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Employees/Create', [
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'nik' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'joined_at' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Financial & Payroll validations
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'payroll_cycle' => ['required', 'in:monthly,weekly'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'fixed_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'late_penalty_type' => ['nullable', 'in:prorate,flat'],
            'late_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'alpha_penalty_type' => ['nullable', 'in:prorate,flat'],
            'alpha_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'in:0,1'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'in:single,married,married_merged'],
            'dependents' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($request, $company) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => 'employee',
                'company_id' => $company->id,
            ]);

            Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'gender' => $request->input('gender'),
                'nik' => $request->input('nik'),
                'nip' => $request->input('nip'),
                'branch_id' => $request->input('branch_id'),
                'division_id' => $request->input('division_id'),
                'position_id' => $request->input('position_id'),
                'joined_at' => $request->input('joined_at'),
                'birth_date' => $request->input('birth_date'),
                'address' => $request->input('address'),
                
                // Bank Details
                'bank_name' => $request->input('bank_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_account_name' => $request->input('bank_account_name'),

                // Salary & Allowances
                'payroll_cycle' => $request->input('payroll_cycle', 'monthly'),
                'basic_salary' => $request->input('basic_salary', 0),
                'fixed_allowance' => $request->input('fixed_allowance', 0),
                'daily_allowance' => $request->input('daily_allowance', 0),
                'other_allowance' => $request->input('other_allowance', 0),
                'overtime_rate_per_hour' => $request->input('overtime_rate_per_hour', 0),

                // Penalties
                'late_penalty_type' => $request->input('late_penalty_type', 'prorate'),
                'late_penalty_nominal' => $request->input('late_penalty_nominal') ?: 0,
                'alpha_penalty_type' => $request->input('alpha_penalty_type', 'prorate'),
                'alpha_penalty_nominal' => $request->input('alpha_penalty_nominal') ?: 0,

                // Tax & BPJS
                'npwp' => $request->input('npwp_number'),
                'ptkp_status' => (match ($request->input('marital_status')) {
                    'married' => 'K/',
                    'married_merged' => 'K/I/',
                    default => 'TK/',
                }) . min(max((int)$request->input('dependents', 0), 0), 3),
                'taxable' => $request->input('is_taxable') == '1',
                'bpjs_kesehatan_no' => $request->input('bpjs_kesehatan_number'),
                'jht_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jp_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jkk_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jkm_no' => $request->input('bpjs_ketenagakerjaan_number'),
            ]);
        });

        return redirect()->route('owner.management.employees.index')
            ->with('success', 'Karyawan baru berhasil ditambahkan.');
    }

    public function show(Employee $employee)
    {
        $company = auth()->user()->company;
        
        if (!$company || $employee->company_id !== $company->id) {
            abort(403, 'Akses ditolak.');
        }

        $employee->load(['branch', 'division', 'position', 'user']);

        return Inertia::render('Owner/Management/Employees/Show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee)
    {
        $company = auth()->user()->company;
        
        if (!$company || $employee->company_id !== $company->id) {
            abort(403, 'Akses ditolak.');
        }

        $employee->load(['branch', 'division', 'position', 'user']);

        $branches = $company->branches()->orderBy('name')->get(['id', 'name']);
        $divisions = $company->divisions()->orderBy('name')->get(['id', 'name']);
        $positions = $company->positions()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Employees/Edit', [
            'employee' => $employee,
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $company = auth()->user()->company;

        if (!$company || $employee->company_id !== $company->id) {
            abort(403, 'Akses ditolak.');
        }

        $user = $employee->user;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users', 'email')->ignore($user?->id),
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'nik' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'joined_at' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            // Financial & Payroll validations
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'payroll_cycle' => ['required', 'in:monthly,weekly'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'fixed_allowance' => ['nullable', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'late_penalty_type' => ['nullable', 'in:prorate,flat'],
            'late_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'alpha_penalty_type' => ['nullable', 'in:prorate,flat'],
            'alpha_penalty_nominal' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'in:0,1'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'in:single,married,married_merged'],
            'dependents' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($request, $employee, $user) {
            if ($user) {
                $userData = [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                ];

                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->input('password'));
                }

                $user->update($userData);
            }

            $employee->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'gender' => $request->input('gender'),
                'nik' => $request->input('nik'),
                'nip' => $request->input('nip'),
                'branch_id' => $request->input('branch_id'),
                'division_id' => $request->input('division_id'),
                'position_id' => $request->input('position_id'),
                'joined_at' => $request->input('joined_at'),
                'birth_date' => $request->input('birth_date'),
                'address' => $request->input('address'),
                'is_active' => $request->boolean('is_active', true),

                // Bank Details
                'bank_name' => $request->input('bank_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_account_name' => $request->input('bank_account_name'),

                // Salary & Allowances
                'payroll_cycle' => $request->input('payroll_cycle', 'monthly'),
                'basic_salary' => $request->input('basic_salary', 0),
                'fixed_allowance' => $request->input('fixed_allowance', 0),
                'daily_allowance' => $request->input('daily_allowance', 0),
                'other_allowance' => $request->input('other_allowance', 0),
                'overtime_rate_per_hour' => $request->input('overtime_rate_per_hour', 0),

                // Penalties
                'late_penalty_type' => $request->input('late_penalty_type', 'prorate'),
                'late_penalty_nominal' => $request->input('late_penalty_nominal') ?: 0,
                'alpha_penalty_type' => $request->input('alpha_penalty_type', 'prorate'),
                'alpha_penalty_nominal' => $request->input('alpha_penalty_nominal') ?: 0,

                // Tax & BPJS
                'npwp' => $request->input('npwp_number'),
                'ptkp_status' => (match ($request->input('marital_status')) {
                    'married' => 'K/',
                    'married_merged' => 'K/I/',
                    default => 'TK/',
                }) . min(max((int)$request->input('dependents', 0), 0), 3),
                'taxable' => $request->input('is_taxable') == '1',
                'bpjs_kesehatan_no' => $request->input('bpjs_kesehatan_number'),
                'jht_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jp_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jkk_no' => $request->input('bpjs_ketenagakerjaan_number'),
                'jkm_no' => $request->input('bpjs_ketenagakerjaan_number'),
            ]);
        });

        return redirect()->route('owner.management.employees.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $company = auth()->user()->company;

        if (!$company || $employee->company_id !== $company->id) {
            abort(403, 'Akses ditolak.');
        }

        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->delete();
            } else {
                $employee->delete();
            }
        });

        return redirect()->route('owner.management.employees.index')
            ->with('success', 'Karyawan berhasil dihapus.');
    }
}
