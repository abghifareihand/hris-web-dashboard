<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('owner:id,name,email')
            ->withCount('employees');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Companies/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:companies,email',
            'password' => 'required|string|min:8',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'owner',
            ]);

            Company::create([
                'user_id' => $user->id,
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'province' => $validated['province'],
                'city' => $validated['city'],
                'business_type' => $validated['business_type'],
            ]);
        });

        return redirect()->route('admin.companies.index')->with('success', 'Perusahaan dan akun pemilik berhasil didaftarkan.');
    }

    public function edit(Company $company)
    {
        $company->load('owner:id,name,email');

        return Inertia::render('Admin/Companies/Edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($company->user_id),
                Rule::unique('companies', 'email')->ignore($company->id),
            ],
            'password' => 'nullable|string|min:8',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $company) {
            $user = $company->owner;
            if ($user) {
                $user->name = $validated['name'];
                $user->email = $validated['email'];
                if (!empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();
            }

            $company->update([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'province' => $validated['province'],
                'city' => $validated['city'],
                'business_type' => $validated['business_type'],
            ]);
        });

        return redirect()->route('admin.companies.index')->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company)
    {
        $user = $company->owner;
        if ($user) {
            $user->delete();
        } else {
            $company->delete();
        }

        return redirect()->route('admin.companies.index')->with('success', 'Perusahaan berhasil dihapus dari sistem.');
    }
}
