<?php

namespace App\Http\Controllers\Web\Owner\Management\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->company;
        $query = Branch::where('company_id', $company->id)->withCount('employees');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('timezone')) {
            $query->where('timezone', $request->timezone);
        }

        $branches = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Company/Branches/Index', [
            'branches' => $branches,
            'filters' => [
                'search' => $request->search,
                'timezone' => $request->timezone,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Owner/Management/Company/Branches/Create');
    }

    public function store(Request $request)
    {
        $company = Auth::user()->company;

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id);
                }),
            ],
            'timezone' => 'required|string|in:WIB,WITA,WIT',
            'radius' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string',
        ]);

        Branch::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'code' => $request->code,
            'timezone' => $request->timezone,
            'radius' => $request->radius,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return redirect()->route('owner.management.company.branches.index')
            ->with('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function edit(Branch $branch)
    {
        abort_if($branch->company_id !== Auth::user()->company->id, 403);

        return Inertia::render('Owner/Management/Company/Branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $company = Auth::user()->company;
        abort_if($branch->company_id !== $company->id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id);
                })->ignore($branch->id),
            ],
            'timezone' => 'required|string|in:WIB,WITA,WIT',
            'radius' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'required|string',
        ]);

        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
            'timezone' => $request->timezone,
            'radius' => $request->radius,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return redirect()->route('owner.management.company.branches.index')
            ->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        abort_if($branch->company_id !== Auth::user()->company->id, 403);
        $branch->delete();

        return redirect()->route('owner.management.company.branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
