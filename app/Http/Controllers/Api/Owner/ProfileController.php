<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get owner user details and company information.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Anda bukan owner/company.'
            ], 403);
        }

        $company = $user->company;
        $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $logoUrl = ($company && $company->logo) ? asset('storage/' . $company->logo) : null;

        return response()->json([
            'status' => true,
            'message' => 'Profil owner berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $avatarUrl,
                    'role' => $user->role,
                ],
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name_company,
                    'name_company' => $company->name_company,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'province' => $company->province,
                    'city' => $company->city,
                    'business_type' => $company->business_type,
                    'logo' => $company->logo,
                    'logo_url' => $logoUrl,
                    'total_employees' => $company->employees()->count(),
                ] : null,
            ]
        ]);
    }

    /**
     * Update owner profile data, avatar, and/or company information.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Anda bukan owner/company.'
            ], 403);
        }

        $company = $user->company;

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',

            // Company fields
            'name_company' => 'nullable|string|max:255',
            'company_email' => 'nullable|string|email|max:255|unique:companies,email,' . ($company ? $company->id : 'NULL'),
            'company_phone' => 'nullable|string|max:20',
            'business_type' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'avatar.image' => 'Berkas avatar harus berupa gambar.',
            'avatar.mimes' => 'Format avatar harus JPEG, PNG, JPG, atau SVG.',
            'avatar.max' => 'Ukuran avatar maksimal 2MB.',
            'logo.image' => 'Berkas logo perusahaan harus berupa gambar.',
            'logo.mimes' => 'Format logo perusahaan harus JPEG, PNG, JPG, atau SVG.',
            'logo.max' => 'Ukuran logo perusahaan maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::transaction(function () use ($request, $user, $company) {
            // Update User
            if ($request->filled('name')) {
                $user->name = $request->name;
            }
            if ($request->filled('email')) {
                $user->email = $request->email;
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->avatar = $request->file('avatar')->store('user-avatars', 'public');
            }

            $user->save();

            // Update Company if provided and exists
            if ($company) {
                $companyData = [];
                if ($request->filled('name_company')) {
                    $companyData['name_company'] = $request->name_company;
                }
                if ($request->filled('company_email')) {
                    $companyData['email'] = $request->company_email;
                }
                if ($request->filled('company_phone')) {
                    $companyData['phone'] = $request->company_phone;
                }
                if ($request->filled('business_type')) {
                    $companyData['business_type'] = $request->business_type;
                }
                if ($request->filled('province')) {
                    $companyData['province'] = $request->province;
                }
                if ($request->filled('city')) {
                    $companyData['city'] = $request->city;
                }

                if ($request->hasFile('logo')) {
                    if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                        Storage::disk('public')->delete($company->logo);
                    }
                    $companyData['logo'] = $request->file('logo')->store('company-logos', 'public');
                }

                if (!empty($companyData)) {
                    $company->update($companyData);
                }
            }
        });

        $company = $user->fresh()->company;
        $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $logoUrl = ($company && $company->logo) ? asset('storage/' . $company->logo) : null;

        return response()->json([
            'status' => true,
            'message' => 'Profil owner berhasil diperbarui.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $avatarUrl,
                    'role' => $user->role,
                ],
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name_company,
                    'name_company' => $company->name_company,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'province' => $company->province,
                    'city' => $company->city,
                    'business_type' => $company->business_type,
                    'logo' => $company->logo,
                    'logo_url' => $logoUrl,
                ] : null,
            ]
        ]);
    }

    /**
     * Change password for the authenticated owner.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Kata sandi saat ini yang Anda masukkan salah.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}
