<?php

namespace App\Http\Controllers\Web\Owner\Management\Company;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->company;
        $query = Division::where('company_id', $company->id)->withCount('employees');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_attendance_schedule')) {
            $query->where('is_attendance_schedule', $request->boolean('is_attendance_schedule'));
        }

        if ($request->filled('is_attendance_radius')) {
            $query->where('is_attendance_radius', $request->boolean('is_attendance_radius'));
        }

        $divisions = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Owner/Management/Company/Divisions/Index', [
            'divisions' => $divisions,
            'filters' => [
                'search' => $request->search,
                'is_attendance_schedule' => $request->is_attendance_schedule,
                'is_attendance_radius' => $request->is_attendance_radius,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Owner/Management/Company/Divisions/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_attendance_schedule' => 'nullable|boolean',
            'is_attendance_radius' => 'nullable|boolean',
        ]);

        Division::create([
            'company_id' => Auth::user()->company->id,
            'name' => $request->name,
            'is_attendance_schedule' => $request->boolean('is_attendance_schedule'),
            'is_attendance_radius' => $request->boolean('is_attendance_radius'),
        ]);

        return redirect()->route('owner.management.company.divisions.index')
            ->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division)
    {
        abort_if($division->company_id !== Auth::user()->company->id, 403);

        return Inertia::render('Owner/Management/Company/Divisions/Edit', [
            'division' => $division,
        ]);
    }

    public function update(Request $request, Division $division)
    {
        abort_if($division->company_id !== Auth::user()->company->id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'is_attendance_schedule' => 'nullable|boolean',
            'is_attendance_radius' => 'nullable|boolean',
        ]);

        $division->update([
            'name' => $request->name,
            'is_attendance_schedule' => $request->boolean('is_attendance_schedule'),
            'is_attendance_radius' => $request->boolean('is_attendance_radius'),
        ]);

        return redirect()->route('owner.management.company.divisions.index')
            ->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division)
    {
        abort_if($division->company_id !== Auth::user()->company->id, 403);
        $division->delete();

        return redirect()->route('owner.management.company.divisions.index')
            ->with('success', 'Divisi berhasil dihapus.');
    }
}
