<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyThrSetting;
use Inertia\Inertia;

class ThrSettingController extends Controller
{
    /**
     * Display the THR settings page.
     */
    public function index()
    {
        $company = auth()->user()->company;
        $settings = $company->thrSetting()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'is_active' => true,
                'min_months_tenure' => 1,
                'full_thr_months_tenure' => 12,
                'basis_of_thr' => 'basic_salary',
                'include_fixed_allowance' => false,
            ]
        );

        return Inertia::render('Owner/Finance/Thr/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the THR settings.
     */
    public function store(Request $request)
    {
        $request->validate([
            'is_active' => 'nullable|boolean',
            'min_months_tenure' => 'required|integer|min:1',
            'full_thr_months_tenure' => 'required|integer|min:1',
            'include_fixed_allowance' => 'nullable|boolean',
        ]);

        $company = auth()->user()->company;

        CompanyThrSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'is_active' => (bool) $request->input('is_active', false),
                'min_months_tenure' => (int) $request->min_months_tenure,
                'full_thr_months_tenure' => (int) $request->full_thr_months_tenure,
                'include_fixed_allowance' => (bool) $request->input('include_fixed_allowance', false),
            ]
        );

        return redirect()->back()->with('success', 'Pengaturan THR berhasil disimpan.');
    }
}
