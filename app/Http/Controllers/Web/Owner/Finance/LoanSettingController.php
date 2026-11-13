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

        return Inertia::render('Owner/System/Settings/Loan/Index', [
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
            'max_loan_amount_weekly' => 'nullable',
            'max_loan_amount_daily' => 'nullable',
            'max_tenor_months' => 'nullable|integer|min:1|max:60',
            'max_tenor_weeks' => 'nullable|integer|min:1|max:52',
            'max_tenor_days' => 'nullable|integer|min:1|max:90',
            'due_date_day' => 'nullable|integer|min:1|max:31',
        ]);

        $cleanAmount = function ($val) {
            if ($val === null || $val === '') return null;
            $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
            return $cleaned !== '' ? (int) $cleaned : null;
        };

        $maxLoanAmount = $cleanAmount($request->input('max_loan_amount'));
        $maxLoanAmountWeekly = $cleanAmount($request->input('max_loan_amount_weekly'));
        $maxLoanAmountDaily = $cleanAmount($request->input('max_loan_amount_daily'));

        $maxTenorMonths = $request->filled('max_tenor_months') ? (int) $request->max_tenor_months : null;
        $maxTenorWeeks = $request->filled('max_tenor_weeks') ? (int) $request->max_tenor_weeks : null;
        $maxTenorDays = $request->filled('max_tenor_days') ? (int) $request->max_tenor_days : null;
        $dueDateDay = $request->filled('due_date_day') ? (int) $request->due_date_day : null;

        CompanyLoanSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'max_loan_amount' => $maxLoanAmount,
                'max_loan_amount_weekly' => $maxLoanAmountWeekly,
                'max_loan_amount_daily' => $maxLoanAmountDaily,
                'max_tenor_months' => $maxTenorMonths,
                'max_tenor_weeks' => $maxTenorWeeks,
                'max_tenor_days' => $maxTenorDays,
                'due_date_day' => $dueDateDay,
            ]
        );

        return redirect()->back()->with('success', 'Pengaturan pinjaman berhasil disimpan.');
    }
}
