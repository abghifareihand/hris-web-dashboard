<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\PendingEmployee;
use App\Models\User;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PendingEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $query = PendingEmployee::where('company_id', $company->id)
            ->where('status', 'pending')
            ->with(['branch:id,name', 'division:id,name', 'position:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
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

        $branches = Branch::where('company_id', $company->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $divisions = Division::where('company_id', $company->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $positions = Position::where('company_id', $company->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $pendingEmployees = $query->latest()
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Employees/Pending', [
            'pendingEmployees' => $pendingEmployees,
            'branches' => $branches,
            'divisions' => $divisions,
            'positions' => $positions,
            'filters' => [
                'search' => $request->search,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'position_id' => $request->position_id,
            ],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $pending = PendingEmployee::where('company_id', $company->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        if (User::where('email', $pending->email)->exists() || Employee::where('email', $pending->email)->exists()) {
            return redirect()->back()->with('error', 'Email pendaftar sudah terdaftar di sistem.');
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'password' => $pending->password,
                'role' => 'employee',
                'company_id' => $company->id,
            ]);

            Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'branch_id' => $pending->branch_id,
                'division_id' => $pending->division_id,
                'position_id' => $pending->position_id,
                'name' => $pending->name,
                'email' => $pending->email,
                'phone' => $pending->phone,
                'address' => $pending->address,
                'is_active' => true,
            ]);

            $pending->update(['status' => 'approved']);

            DB::commit();

            return redirect()->route('owner.management.employees.pending.index')
                ->with('success', 'Karyawan berhasil disetujui dan ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $pending = PendingEmployee::where('company_id', $company->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $pending->update(['status' => 'rejected']);

        return redirect()->route('owner.management.employees.pending.index')
            ->with('success', 'Pendaftaran calon karyawan telah ditolak.');
    }
}
