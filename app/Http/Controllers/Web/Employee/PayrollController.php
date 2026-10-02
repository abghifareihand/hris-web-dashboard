<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\PayrollItem;
use App\Models\ThrItem;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $payrolls = PayrollItem::with('payroll')
            ->where('employee_id', $employee->id)
            ->whereHas('payroll', function ($q) {
                $q->where('status', 'paid');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $thrItems = ThrItem::with('thr')
            ->where('employee_id', $employee->id)
            ->whereHas('thr', function ($q) {
                $q->where('status', 'paid');
            })
            ->latest()
            ->get();

        return Inertia::render('Employee/Payrolls/Index', [
            'payrolls' => $payrolls,
            'thrItems' => $thrItems,
        ]);
    }
}
