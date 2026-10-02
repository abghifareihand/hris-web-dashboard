<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use App\Models\CompanyLoanSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanSettingController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $setting = $company ? $company->loanSetting : null;

        return Inertia::render('Owner/Finance/Loans/Settings', [
            'setting' => $setting,
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            abort(403, 'Perusahaan tidak ditemukan.');
        }

        $request->validate([
            'max_loan_amount' => 'nullable',
            'max_tenor_months' => 'nullable|integer|min:1|max:60',
            'due_date_day' => 'nullable|integer|min:1|max:30',
        ]);

        $maxLoanAmount = null;
        if ($request->filled('max_loan_amount')) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $request->max_loan_amount);
            $maxLoanAmount = $cleaned !== '' ? (int) $cleaned : null;
        }

        $maxTenorMonths = $request->filled('max_tenor_months') ? (int) $request->max_tenor_months : null;
        $dueDateDay = $request->filled('due_date_day') ? (int) $request->due_date_day : null;

        CompanyLoanSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'max_loan_amount' => $maxLoanAmount,
                'max_tenor_months' => $maxTenorMonths,
                'due_date_day' => $dueDateDay,
            ]
        );

        return redirect()->back()->with('success', 'Pengaturan pinjaman berhasil disimpan.');
    }
}
