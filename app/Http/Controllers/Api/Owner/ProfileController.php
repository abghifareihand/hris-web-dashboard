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
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'logo_url' => $logoUrl,
                ] : null,
            ]
        ]);
    }

    /**
     * Update owner personal profile data and/or avatar.
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

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'delete_avatar' => 'nullable|boolean',
        ], [
            'name.string' => 'Nama harus berupa teks.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'avatar.image' => 'Berkas avatar harus berupa gambar.',
            'avatar.mimes' => 'Format avatar harus JPEG, PNG, JPG, atau SVG.',
            'avatar.max' => 'Ukuran avatar maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

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
        } elseif ($request->boolean('delete_avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        $user->save();

        $company = $user->company;
        $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $logoUrl = ($company && $company->logo) ? asset('storage/' . $company->logo) : null;

        return response()->json([
            'status' => true,
            'message' => 'Profil owner berhasil diperbarui.',
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
