<?php

namespace App\Http\Controllers\Web\Owner\Management\Company;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Branch;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->company;
        $query = Position::where('company_id', $company->id)->with(['branches', 'divisions'])->withCount('employees');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('branches', function($q) use ($request) {
                $q->where('branches.id', $request->branch_id);
            });
        }

        if ($request->filled('division_id')) {
            $query->whereHas('divisions', function($q) use ($request) {
                $q->where('divisions.id', $request->division_id);
            });
        }

        $positions = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Company/Positions/Index', [
            'positions' => $positions,
            'branches' => $branches,
            'divisions' => $divisions,
            'filters' => [
                'search' => $request->search,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
            ],
        ]);
    }

    public function create()
    {
        $company = Auth::user()->company;
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Company/Positions/Create', [
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $company = Auth::user()->company;

        $request->validate([
            'name' => 'required|string|max:255',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
            'divisions' => 'nullable|array',
            'divisions.*' => 'exists:divisions,id',
        ]);

        $position = Position::create([
            'company_id' => $company->id,
            'name' => $request->name,
        ]);

        if ($request->filled('branches')) {
            $position->branches()->sync($request->branches);
        }

        if ($request->filled('divisions')) {
            $position->divisions()->sync($request->divisions);
        }

        return redirect()->route('owner.management.company.positions.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Position $position)
    {
        $company = Auth::user()->company;
        abort_if($position->company_id !== $company->id, 403);

        $position->load(['branches', 'divisions']);
        $branches = Branch::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $divisions = Division::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Owner/Management/Company/Positions/Edit', [
            'position' => $position,
            'branches' => $branches,
            'divisions' => $divisions,
        ]);
    }

    public function update(Request $request, Position $position)
    {
        $company = Auth::user()->company;
        abort_if($position->company_id !== $company->id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
            'divisions' => 'nullable|array',
            'divisions.*' => 'exists:divisions,id',
        ]);

        $position->update([
            'name' => $request->name,
        ]);

        $position->branches()->sync($request->branches ?? []);
        $position->divisions()->sync($request->divisions ?? []);

        return redirect()->route('owner.management.company.positions.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position)
    {
        abort_if($position->company_id !== Auth::user()->company->id, 403);
        $position->delete();

        return redirect()->route('owner.management.company.positions.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
