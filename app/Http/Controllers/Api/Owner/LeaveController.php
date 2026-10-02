<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveCategory;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeaveController extends Controller
{
    // ==========================================
    // 1. LEAVE CATEGORIES (Kategori Cuti)
    // ==========================================

    public function getCategories(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $query = LeaveCategory::where('company_id', $company->id)->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->paginate($this->getPerPage($request));

        return $this->paginatedResponse($categories, 'Data kategori cuti berhasil diambil.');
    }

    public function storeCategory(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'default_quota' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = LeaveCategory::create([
                'company_id' => $company->id,
                'name' => $request->name,
                'default_quota' => $request->default_quota,
            ]);

            // Auto-generate balances for all active employees for the current year
            $employees = Employee::where('company_id', $company->id)->get();
            $currentYear = date('Y');

            $balances = [];
            foreach ($employees as $employee) {
                $balances[] = [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'leave_category_id' => $category->id,
                    'year' => $currentYear,
                    'quota' => $category->default_quota,
                    'used' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            LeaveBalance::insert($balances);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Kategori cuti berhasil ditambahkan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCategory(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $category = LeaveCategory::where('company_id', $company->id)->find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Kategori cuti tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'default_quota' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $category->update([
            'name' => $request->name,
            'default_quota' => $request->default_quota,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kategori cuti berhasil diperbarui.'
        ]);
    }

    public function deleteCategory(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $category = LeaveCategory::where('company_id', $company->id)->find($id);
        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Kategori cuti tidak ditemukan.'], 404);
        }

        // Check if there are any approved leave requests using this category
        $hasApprovedRequests = LeaveRequest::where('leave_category_id', $category->id)
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedRequests) {
            return response()->json([
                'status' => false,
                'message' => 'Kategori ini tidak dapat dihapus karena sudah memiliki riwayat cuti disetujui.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            // Delete related balances first
            LeaveBalance::where('leave_category_id', $category->id)->delete();
            // Delete related requests (pending, cancelled, rejected)
            LeaveRequest::where('leave_category_id', $category->id)->delete();
            // Delete category
            $category->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Kategori cuti berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus kategori cuti.'
            ], 500);
        }
    }


    // ==========================================
    // 2. LEAVE BALANCES (Jatah Cuti Karyawan)
    // ==========================================

    public function getBalances(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $currentYear = $request->input('year', date('Y'));

        $query = Employee::select('id', 'name', 'email')
            ->where('company_id', $company->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // We can optionally filter to employees that have a specific leave category if requested
        if ($request->filled('leave_category_id')) {
            $query->whereHas('leaveBalances', function ($q) use ($request, $currentYear) {
                $q->where('year', $currentYear)
                  ->where('leave_category_id', $request->leave_category_id);
            });
        } else {
            // Jika tidak difilter per kategori, filter agar HANYA menampilkan karyawan yang memiliki saldo (minimal 1) di tahun tersebut
            $query->whereHas('leaveBalances', function ($q) use ($currentYear) {
                $q->where('year', $currentYear);
            });
        }

        // Load the balances for the current year
        $query->with(['leaveBalances' => function ($q) use ($currentYear) {
            $q->where('year', $currentYear)->with('leaveCategory:id,name,default_quota');
        }]);

        $employees = $query->orderBy('name', 'asc')->paginate($this->getPerPage($request));
        
        $employees->getCollection()->transform(function ($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'balances' => $employee->leaveBalances->map(function ($balance) {
                    return [
                        'id' => $balance->id,
                        'year' => $balance->year,
                        'quota' => $balance->quota,
                        'used' => $balance->used,
                        'remaining' => max(0, $balance->quota - $balance->used),
                        'category' => $balance->leaveCategory ? [
                            'id' => $balance->leaveCategory->id,
                            'name' => $balance->leaveCategory->name,
                            'default_quota' => $balance->leaveCategory->default_quota,
                        ] : null,
                    ];
                }),
            ];
        });

        return $this->paginatedResponse($employees, 'Data jatah cuti karyawan berhasil diambil.');
    }

    public function updateBalance(Request $request, $employeeId)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $employee = Employee::where('company_id', $company->id)->find($employeeId);
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'balances' => 'required|array',
            'balances.*.leave_balance_id' => 'required|integer',
            'balances.*.quota' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->balances as $item) {
                $balance = LeaveBalance::with('leaveCategory')
                    ->where('employee_id', $employee->id)
                    ->where('company_id', $company->id)
                    ->find($item['leave_balance_id']);

                if (!$balance) {
                    throw new \Exception("Jatah cuti dengan ID {$item['leave_balance_id']} tidak ditemukan.");
                }

                $maxQuota = $balance->leaveCategory ? $balance->leaveCategory->default_quota : 999;
                
                if ($item['quota'] < $balance->used) {
                    throw new \Exception("Kuota cuti {$balance->leaveCategory->name} tidak boleh kurang dari cuti terpakai ({$balance->used} hari).");
                }
                
                if ($item['quota'] > $maxQuota) {
                    throw new \Exception("Kuota cuti {$balance->leaveCategory->name} tidak boleh lebih dari jatah default ({$maxQuota} hari).");
                }

                $balance->update([
                    'quota' => $item['quota'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Jatah cuti karyawan berhasil diperbarui.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    // ==========================================
    // 3. LEAVE REQUESTS (Persetujuan Cuti)
    // ==========================================

    public function getRequests(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $query = LeaveRequest::with(['employee:id,name,email', 'leaveCategory:id,name'])
            ->where('company_id', $company->id);

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
            $query->where('status', $request->status); // pending, approved, rejected, cancelled
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $requests = $query->latest()->paginate($this->getPerPage($request));

        $requests->getCollection()->transform(function ($req) {
            // Get remaining balance for the category and year of the request for this specific employee
            $year = date('Y', strtotime($req->start_date));
            $balance = \App\Models\LeaveBalance::where('employee_id', $req->employee_id)
                ->where('leave_category_id', $req->leave_category_id)
                ->where('year', $year)
                ->first();

            $remainingBalance = 0;
            if ($balance) {
                $remainingBalance = $balance->quota - $balance->used;
            }

            return [
                'id' => $req->id,
                'start_date' => $req->start_date,
                'end_date' => $req->end_date,
                'days_count' => $req->days_count,
                'reason' => $req->reason,
                'reject_reason' => $req->reject_reason,
                'status' => $req->status,
                'remaining_balance' => $remainingBalance,
                'attachment_url' => $req->attachment ? asset('storage/' . $req->attachment) : null,
                'created_at' => $req->created_at,
                'employee' => $req->employee ? [
                    'id' => $req->employee->id,
                    'name' => $req->employee->name,
                    'email' => $req->employee->email,
                ] : null,
                'category' => $req->leaveCategory ? [
                    'id' => $req->leaveCategory->id,
                    'name' => $req->leaveCategory->name,
                ] : null,
            ];
        });

        return $this->paginatedResponse($requests, 'Data pengajuan cuti berhasil diambil.');
    }

    public function approveRequest(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $leaveRequest = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'status' => false, 
                'message' => 'Pengajuan cuti pending tidak ditemukan.'
            ], 404);
        }

        try {
            DB::beginTransaction();
            $currentYear = date('Y', strtotime($leaveRequest->start_date));

            // Find balance
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_category_id', $leaveRequest->leave_category_id)
                ->where('year', $currentYear)
                ->lockForUpdate() // lock for concurrency
                ->first();

            if (!$balance) {
                throw new \Exception('Saldo cuti karyawan untuk tahun ' . $currentYear . ' tidak ditemukan.');
            }

            // Enforce quota limits
            $remaining = $balance->quota - $balance->used;
            if ($leaveRequest->days_count > $remaining) {
                throw new \Exception('Saldo cuti karyawan tidak mencukupi untuk menyetujui pengajuan ini.');
            }

            // Update balance
            $balance->increment('used', $leaveRequest->days_count);

            // Update request
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now()
            ]);

            // Sync approved leave to Attendance records
            $period = \Carbon\CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
            $categoryName = $leaveRequest->leaveCategory->name ?? 'Cuti';
            foreach ($period as $dt) {
                \App\Models\Attendance::updateOrCreate(
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

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Pengajuan cuti berhasil disetujui.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function rejectRequest(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reject_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $leaveRequest = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'status' => false, 
                'message' => 'Pengajuan cuti pending tidak ditemukan.'
            ], 404);
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'approved_by' => $request->user()->id,
            'approved_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan cuti telah ditolak.'
        ]);
    }

    public function deleteRequest(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['status' => false, 'message' => 'Hanya owner perusahaan yang bisa mengakses.'], 403);
        }

        $leaveRequest = LeaveRequest::where('company_id', $company->id)->find($id);

        if (!$leaveRequest) {
            return response()->json([
                'status' => false, 
                'message' => 'Pengajuan cuti tidak ditemukan.'
            ], 404);
        }

        if ($leaveRequest->status === 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan cuti yang sudah disetujui tidak dapat dihapus. Silakan batalkan terlebih dahulu jika perlu (hubungi tim dukungan).'
            ], 422);
        }

        $leaveRequest->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan cuti berhasil dihapus.'
        ]);
    }
}
