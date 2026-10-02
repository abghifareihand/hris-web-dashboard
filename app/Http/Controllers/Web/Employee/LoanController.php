<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Loan;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Loan::with('installments')->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->latest('date')->paginate(10)->withQueryString();

        $totalActiveLoan = (float) Loan::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('amount');

        return Inertia::render('Employee/Loans/Index', [
            'loans' => $loans,
            'totalActiveLoan' => $totalActiveLoan,
            'filters' => [
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:50000',
            'tenor' => 'required|integer|min:1|max:24',
            'description' => 'required|string|max:1000',
        ]);

        Loan::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'tenor' => $request->tenor,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan pinjaman / kasbon berhasil dikirim.');
    }
}
