<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\LeaveCategory;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Attendance;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function pending(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = LeaveRequest::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.branch:id,name',
            'employee.division:id,name',
            'leaveCategory:id,name'
        ])
            ->where('company_id', $company->id)
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $pendingLeaves = $query->latest()
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Leaves/Pending', [
            'pendingLeaves' => $pendingLeaves,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $leaveRequest = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        try {
            DB::transaction(function () use ($leaveRequest, $request) {
                $currentYear = date('Y', strtotime($leaveRequest->start_date));

                $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                    ->where('leave_category_id', $leaveRequest->leave_category_id)
                    ->where('year', $currentYear)
                    ->first();

                if (!$balance) {
                    throw new \Exception('Saldo cuti karyawan untuk tahun ' . $currentYear . ' tidak ditemukan.');
                }

                $remaining = $balance->quota - $balance->used;
                if ($leaveRequest->days_count > $remaining) {
                    throw new \Exception('Saldo cuti karyawan tidak mencukupi untuk menyetujui pengajuan ini.');
                }

                $balance->increment('used', $leaveRequest->days_count);

                $leaveRequest->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                // Sync attendance records
                $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
                $categoryName = $leaveRequest->leaveCategory->name ?? 'Cuti';
                foreach ($period as $dt) {
                    Attendance::updateOrCreate(
                        [
                            'company_id' => $leaveRequest->company_id,
                            'employee_id' => $leaveRequest->employee_id,
                            'date' => $dt->format('Y-m-d'),
                        ],
                        [
                            'attendance_status' => 'leave',
                            'notes' => 'Cuti: ' . $categoryName . ($leaveRequest->reason ? ' (' . $leaveRequest->reason . ')' : ''),
                        ]
                    );
                }
            });

            return redirect()->back()->with('success', 'Pengajuan cuti berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $leaveRequest = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $leaveRequest->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan cuti telah ditolak.');
    }

    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = LeaveRequest::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.branch:id,name',
            'employee.division:id,name',
            'leaveCategory:id,name'
        ])
            ->where('leave_requests.company_id', $company->id)
            ->where('status', '!=', 'pending');

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $leaves = $query->latest('start_date')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Leaves/Index', [
            'leaves' => $leaves,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    public function balance(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $currentYear = (int) $request->input('year', date('Y'));
        
        $query = LeaveBalance::with([
            'employee:id,name,nip,branch_id,division_id',
            'employee.branch:id,name',
            'employee.division:id,name',
            'leaveCategory:id,name'
        ])
            ->where('leave_balances.company_id', $company->id)
            ->where('leave_balances.year', $currentYear);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }

        if ($request->filled('leave_category_id')) {
            $query->where('leave_category_id', $request->leave_category_id);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nip', 'like', '%' . $request->search . '%');
            });
        }

        $balances = $query->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $leaveCategories = LeaveCategory::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Leaves/Balance/Index', [
            'balances' => $balances,
            'branches' => $branches,
            'divisions' => $divisions,
            'leaveCategories' => $leaveCategories,
            'currentYear' => $currentYear,
            'filters' => [
                'year' => $currentYear,
                'search' => $request->search,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'leave_category_id' => $request->leave_category_id,
            ],
        ]);
    }

    public function editBalance(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $balance = LeaveBalance::with(['employee:id,name,nip', 'leaveCategory:id,name'])
            ->where('company_id', $company->id)
            ->findOrFail($id);

        return Inertia::render('Owner/Management/Leaves/Balance/Edit', [
            'balance' => $balance,
        ]);
    }

    public function updateBalance(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $balance = LeaveBalance::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'quota' => 'required|integer|min:0',
            'used' => 'required|integer|min:0',
        ]);

        $balance->update([
            'quota' => $request->quota,
            'used' => $request->used,
        ]);

        return redirect()->route('owner.management.leaves.balance.index')
            ->with('success', 'Saldo cuti karyawan berhasil diperbarui.');
    }

    public function categories(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = LeaveCategory::where('company_id', $company->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Leaves/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function createCategory()
    {
        return Inertia::render('Owner/Management/Leaves/Categories/Create');
    }

    public function storeCategory(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'default_quota' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($company, $request) {
            $category = LeaveCategory::create([
                'company_id' => $company->id,
                'name' => $request->name,
                'default_quota' => $request->default_quota,
            ]);

            // Auto-generate balances for all active employees for the current year
            $employees = Employee::where('company_id', $company->id)->get();
            $currentYear = date('Y');

            foreach ($employees as $employee) {
                LeaveBalance::firstOrCreate([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'leave_category_id' => $category->id,
                    'year' => $currentYear,
                ], [
                    'quota' => $category->default_quota,
                    'used' => 0,
                ]);
            }
        });

        return redirect()->route('owner.management.leaves.categories.index')
            ->with('success', 'Kategori cuti berhasil ditambahkan dan kuota karyawan diinisialisasi.');
    }

    public function editCategory(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $category = LeaveCategory::where('company_id', $company->id)->findOrFail($id);

        return Inertia::render('Owner/Management/Leaves/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $category = LeaveCategory::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'default_quota' => 'required|integer|min:0',
        ]);

        $category->update([
            'name' => $request->name,
            'default_quota' => $request->default_quota,
        ]);

        return redirect()->route('owner.management.leaves.categories.index')
            ->with('success', 'Kategori cuti berhasil diperbarui.');
    }

    public function destroyCategory(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $category = LeaveCategory::where('company_id', $company->id)->findOrFail($id);
        $category->delete();

        return redirect()->route('owner.management.leaves.categories.index')
            ->with('success', 'Kategori cuti berhasil dihapus.');
    }
}
