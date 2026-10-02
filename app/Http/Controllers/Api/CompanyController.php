<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function searchCompany(Request $request)
    {
        $search = $request->query('search');

        if (empty($search) || strlen($search) < 3) {
            return response()->json([
                'status' => true,
                'message' => 'Pencarian minimal 3 karakter.',
                'data' => []
            ]);
        }

        $companies = Company::where('name_company', 'like', "%{$search}%")
            ->select('id', 'name_company as name')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar perusahaan berhasil diambil.',
            'data' => $companies
        ]);
    }

    public function getStructureCompany($id)
    {
        $company = Company::with(['branches', 'divisions', 'positions'])->find($id);

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Perusahaan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Struktur perusahaan berhasil diambil.',
            'data' => [
                'branches' => $company->branches->map(function($branch) {
                    return ['id' => $branch->id, 'name' => $branch->name];
                }),
                'divisions' => $company->divisions->map(function($division) {
                    return ['id' => $division->id, 'name' => $division->name];
                }),
                'positions' => $company->positions->map(function($position) {
                    return ['id' => $position->id, 'name' => $position->name];
                })
            ]
        ]);
    }
}
