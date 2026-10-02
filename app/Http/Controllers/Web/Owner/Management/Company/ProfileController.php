<?php

namespace App\Http\Controllers\Web\Owner\Management\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        return Inertia::render('Owner/Management/Company/Profile', [
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company) {
            abort(403, 'Anda tidak memiliki akses perusahaan.');
        }

        $validated = $request->validate([
            'name_company' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'business_type' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'delete_logo' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = $request->file('logo')->store('company-logos', 'public');
        } elseif ($request->boolean('delete_logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = null;
        }

        unset($validated['delete_logo']);

        $company->update($validated);

        return redirect()->route('owner.management.company.profile.index')
            ->with('success', 'Profil perusahaan berhasil diperbarui.');
    }
}
