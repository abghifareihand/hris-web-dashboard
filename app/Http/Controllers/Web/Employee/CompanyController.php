<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee()->with(['company', 'branch', 'division'])->first();
        if (!$employee) abort(403);

        $company = $employee->company;
        $branch = $employee->branch;

        $colleagues = Employee::with(['division:id,name', 'position:id,name', 'branch:id,name'])
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->select('id', 'name', 'nip', 'branch_id', 'division_id', 'position_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Employee/Company/Index', [
            'company' => $company,
            'branch' => $branch,
            'colleagues' => $colleagues,
        ]);
    }
}
