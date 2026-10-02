<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Get employees for the owner's company
     * Features search, branch filtering, division filtering and pagination.
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

        $query = Employee::with(['position:id,name', 'division:id,name', 'branch:id,name', 'user:id,email'])
            ->where('company_id', $company->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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

        // Limit results dynamically based on request, default to 10
        $perPage = $this->getPerPage($request);
        $employees = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($employees, 'Data karyawan berhasil diambil.');
    }

    /**
     * Get company structure (branches & divisions) for the owner's company
     * Used for dropdown filters in the app.
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

        $company->load(['branches:id,company_id,name', 'divisions:id,company_id,name']);

        return response()->json([
            'status' => true,
            'message' => 'Struktur perusahaan berhasil diambil.',
            'data' => [
                'branches' => $company->branches,
                'divisions' => $company->divisions,
            ]
        ]);
    }
}
