<?php

namespace App\Http\Controllers\Web\Owner\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxController extends Controller
{
    /**
     * Display PPh 21 informative PTKP & TER table.
     */
    public function pph21()
    {
        $ptkpList = [
            ['code' => 'TK/0', 'description' => 'Tidak Kawin, Tanpa Tanggungan', 'yearly' => 54000000, 'monthly' => 4500000, 'category' => 'Lajang (TK)', 'ter_category' => 'TER A'],
            ['code' => 'TK/1', 'description' => 'Tidak Kawin, 1 Tanggungan', 'yearly' => 58500000, 'monthly' => 4875000, 'category' => 'Lajang (TK)', 'ter_category' => 'TER A'],
            ['code' => 'TK/2', 'description' => 'Tidak Kawin, 2 Tanggungan', 'yearly' => 63000000, 'monthly' => 5250000, 'category' => 'Lajang (TK)', 'ter_category' => 'TER B'],
            ['code' => 'TK/3', 'description' => 'Tidak Kawin, 3 Tanggungan', 'yearly' => 67500000, 'monthly' => 5625000, 'category' => 'Lajang (TK)', 'ter_category' => 'TER B'],
            ['code' => 'K/0', 'description' => 'Kawin, Tanpa Tanggungan', 'yearly' => 58500000, 'monthly' => 4875000, 'category' => 'Kawin (K)', 'ter_category' => 'TER A'],
            ['code' => 'K/1', 'description' => 'Kawin, 1 Tanggungan', 'yearly' => 63000000, 'monthly' => 5250000, 'category' => 'Kawin (K)', 'ter_category' => 'TER B'],
            ['code' => 'K/2', 'description' => 'Kawin, 2 Tanggungan', 'yearly' => 67500000, 'monthly' => 5625000, 'category' => 'Kawin (K)', 'ter_category' => 'TER B'],
            ['code' => 'K/3', 'description' => 'Kawin, 3 Tanggungan', 'yearly' => 72000000, 'monthly' => 6000000, 'category' => 'Kawin (K)', 'ter_category' => 'TER C'],
            ['code' => 'K/I/0', 'description' => 'Kawin, Penghasilan Istri Digabung, Tanpa Tanggungan', 'yearly' => 112500000, 'monthly' => 9375000, 'category' => 'Kawin + Istri Bekerja (K/I)', 'ter_category' => 'TER A'],
            ['code' => 'K/I/1', 'description' => 'Kawin, Penghasilan Istri Digabung, 1 Tanggungan', 'yearly' => 117000000, 'monthly' => 9750000, 'category' => 'Kawin + Istri Bekerja (K/I)', 'ter_category' => 'TER B'],
            ['code' => 'K/I/2', 'description' => 'Kawin, Penghasilan Istri Digabung, 2 Tanggungan', 'yearly' => 121500000, 'monthly' => 10125000, 'category' => 'Kawin + Istri Bekerja (K/I)', 'ter_category' => 'TER B'],
            ['code' => 'K/I/3', 'description' => 'Kawin, Penghasilan Istri Digabung, 3 Tanggungan', 'yearly' => 126000000, 'monthly' => 10500000, 'category' => 'Kawin + Istri Bekerja (K/I)', 'ter_category' => 'TER C'],
        ];

        return Inertia::render('Owner/Finance/Taxes/Pph21', [
            'ptkpList' => $ptkpList,
        ]);
    }

    /**
     * Display BPJS Ketenagakerjaan page.
     */
    public function bpjsKetenagakerjaan()
    {
        $company = auth()->user()->company;
        $settings = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);
        
        return Inertia::render('Owner/Finance/Taxes/BpjsTk', [
            'settings' => $settings,
        ]);
    }

    public function updateBpjsKetenagakerjaan(Request $request)
    {
        $company = auth()->user()->company;
        $settings = $company->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $company->id]);

        $settings->update([
            'jht_active' => (bool) $request->input('jht_active', false),
            'jht_company_percent' => (float) $request->input('jht_company_percent', 3.70),
            'jht_employee_percent' => (float) $request->input('jht_employee_percent', 2.00),
            'jkk_active' => (bool) $request->input('jkk_active', false),
            'jkk_risk_level' => $request->input('jkk_risk_level', 'Sangat Rendah'),
            'jkk_percent' => (float) $request->input('jkk_percent', 0.24),
            'jkm_active' => (bool) $request->input('jkm_active', false),
            'jkm_percent' => (float) $request->input('jkm_percent', 0.30),
            'jp_active' => (bool) $request->input('jp_active', false),
            'jp_company_percent' => (float) $request->input('jp_company_percent', 2.00),
            'jp_employee_percent' => (float) $request->input('jp_employee_percent', 1.00),
            'jp_maximum_wage' => (int) str_replace(['Rp', '.', ' '], '', (string) $request->input('jp_maximum_wage', 10042300)),
            'effective_year' => $request->input('effective_year', date('Y')),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->back()->with('success', 'Pengaturan BPJS Ketenagakerjaan berhasil disimpan.');
    }

    /**
     * Display BPJS Kesehatan page.
     */
    public function bpjsKesehatan()
    {
        $company = auth()->user()->company;
        $settings = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        return Inertia::render('Owner/Finance/Taxes/BpjsKes', [
            'settings' => $settings,
        ]);
    }

    public function updateBpjsKesehatan(Request $request)
    {
        $company = auth()->user()->company;
        $settings = $company->bpjsKesehatan()->firstOrCreate(['company_id' => $company->id]);

        $settings->update([
            'is_active' => (bool) $request->input('is_active', false),
            'company_percent' => (float) $request->input('company_percent', 4.00),
            'employee_percent' => (float) $request->input('employee_percent', 1.00),
            'minimum_wage' => (int) str_replace(['Rp', '.', ' '], '', (string) $request->input('minimum_wage', 2500000)),
            'maximum_wage' => (int) str_replace(['Rp', '.', ' '], '', (string) $request->input('maximum_wage', 12000000)),
            'effective_year' => $request->input('effective_year', date('Y')),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->back()->with('success', 'Pengaturan BPJS Kesehatan berhasil disimpan.');
    }
}
