<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use App\Models\CompanyPayrollSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollSettingController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        if (!$company) {
            abort(403, 'Perusahaan tidak ditemukan.');
        }

        $setting = $company->payrollSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'default_payroll_cycle' => 'monthly',
                'monthly_working_days' => 25,
                'weekly_working_days' => 6,
            ]
        );

        return Inertia::render('Owner/System/Settings/Payroll/Index', [
            'setting' => $setting,
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            abort(403, 'Perusahaan tidak ditemukan.');
        }

        $validated = $request->validate([
            'default_payroll_cycle' => 'required|in:monthly,weekly,daily',
            'monthly_working_days' => 'required|integer|min:1|max:31',
            'weekly_working_days' => 'required|integer|min:1|max:7',
            'notes' => 'nullable|string|max:500',
        ]);

        CompanyPayrollSetting::updateOrCreate(
            ['company_id' => $company->id],
            $validated
        );

        return redirect()->back()->with('success', 'Pengaturan kebijakan penggajian berhasil diperbarui.');
    }
}
