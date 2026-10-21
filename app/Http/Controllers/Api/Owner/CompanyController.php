<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * Get company profile details for the authenticated owner.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 404);
        }

        $logoUrl = $company->logo ? asset('storage/' . $company->logo) : null;

        return response()->json([
            'status' => true,
            'message' => 'Data perusahaan berhasil diambil.',
            'data' => [
                'id' => $company->id,
                'name' => $company->name_company,
                'name_company' => $company->name_company,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'business_type' => $company->business_type,
                'province' => $company->province,
                'city' => $company->city,
                'logo' => $company->logo,
                'logo_url' => $logoUrl,
                'total_employees' => $company->employees()->count(),
                'total_branches' => $company->branches()->count(),
                'total_divisions' => $company->divisions()->count(),
                'total_positions' => $company->positions()->count(),
                'created_at' => $company->created_at ? Carbon::parse($company->created_at)->format('Y-m-d H:i') : null,
                'updated_at' => $company->updated_at ? Carbon::parse($company->updated_at)->format('Y-m-d H:i') : null,
            ],
        ]);
    }

    /**
     * Update company profile details and/or logo.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name_company' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'business_type' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'delete_logo' => 'nullable|boolean',
        ], [
            'email.email' => 'Format email perusahaan tidak valid.',
            'email.unique' => 'Email perusahaan sudah digunakan oleh perusahaan lain.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.mimes' => 'Format logo harus jpeg, png, jpg, svg, atau webp.',
            'logo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [];
        if ($request->filled('name_company')) {
            $data['name_company'] = $request->name_company;
        }
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        if ($request->filled('phone')) {
            $data['phone'] = $request->phone;
        }
        if ($request->has('address')) {
            $data['address'] = $request->address;
        }
        if ($request->filled('business_type')) {
            $data['business_type'] = $request->business_type;
        }
        if ($request->filled('province')) {
            $data['province'] = $request->province;
        }
        if ($request->filled('city')) {
            $data['city'] = $request->city;
        }

        // Logo handling
        if ($request->hasFile('logo')) {
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        } elseif ($request->boolean('delete_logo')) {
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = null;
        }

        if (!empty($data)) {
            $company->update($data);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data perusahaan berhasil diperbarui.',
        ]);
    }
}
