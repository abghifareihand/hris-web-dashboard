<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
                'message' => $validator->errors()->first(),
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

    /**
     * Setup company profile for the authenticated owner (Step 2 of registration).
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner yang dapat melakukan setup perusahaan.',
            ], 403);
        }

        if ($user->company) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah memiliki profil perusahaan.',
                'data' => $user->company,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name_company' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:companies,email',
            'email_company' => 'nullable|string|email|max:255|unique:companies,email',
            'phone' => 'nullable|string|max:25',
            'phone_company' => 'nullable|string|max:25',
            'business_type' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ], [
            'name_company.required' => 'Nama perusahaan wajib diisi.',
            'business_type.required' => 'Jenis bisnis wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'city.required' => 'Kota/Kabupaten wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Support both email / email_company & phone / phone_company
        $companyEmail = $request->input('email_company') ?? $request->input('email');
        if (!$companyEmail) {
            return response()->json([
                'status' => false,
                'message' => 'Email perusahaan wajib diisi.',
            ], 422);
        }

        $companyPhone = $request->input('phone_company') ?? $request->input('phone');
        if (!$companyPhone) {
            return response()->json([
                'status' => false,
                'message' => 'Nomor telepon perusahaan wajib diisi.',
            ], 422);
        }

        // Logo handling
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('company_logos', 'public');
        }

        $company = Company::create([
            'user_id' => $user->id,
            'name_company' => $request->name_company,
            'email' => $companyEmail,
            'phone' => $companyPhone,
            'address' => $request->address,
            'province' => $request->province,
            'city' => $request->city,
            'business_type' => $request->business_type,
            'logo' => $logoPath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Setup data perusahaan berhasil.',
            'data' => $company,
        ], 201);
    }
}
