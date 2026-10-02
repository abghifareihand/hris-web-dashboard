<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get complete profile details of the authenticated employee.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee()
            ->with(['company', 'branch', 'division', 'position'])
            ->first();

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
        $company = $employee->company;

        return response()->json([
            'status' => true,
            'message' => 'Profil berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $avatarUrl,
                    'role' => $user->role,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nik' => $employee->nik,
                    'nip' => $employee->nip,
                    'phone' => $employee->phone,
                    'gender' => $employee->gender,
                    'birth_date' => $employee->birth_date ? Carbon::parse($employee->birth_date)->format('Y-m-d') : null,
                    'address' => $employee->address,
                    'joined_at' => $employee->joined_at ? Carbon::parse($employee->joined_at)->format('Y-m-d') : null,
                    'payroll_cycle' => $employee->payroll_cycle,
                    'is_active' => (bool) $employee->is_active,
                ],
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name_company,
                    'email' => $company->email_company,
                    'phone' => $company->phone_company,
                    'address' => $company->address_company,
                    'logo_url' => $company->logo ? asset('storage/' . $company->logo) : null,
                ] : null,
                'placement' => [
                    'branch' => $employee->branch ? [
                        'id' => $employee->branch->id,
                        'name' => $employee->branch->name,
                        'address' => $employee->branch->address,
                    ] : null,
                    'division' => $employee->division ? [
                        'id' => $employee->division->id,
                        'name' => $employee->division->name,
                    ] : null,
                    'position' => $employee->position ? [
                        'id' => $employee->position->id,
                        'name' => $employee->position->name,
                    ] : null,
                ],
                'bank_account' => [
                    'bank_name' => $employee->bank_name,
                    'bank_account_number' => $employee->bank_account_number,
                    'bank_account_name' => $employee->bank_account_name,
                ],
                'bpjs_and_tax' => [
                    'npwp' => $employee->npwp,
                    'ptkp_status' => $employee->ptkp_status,
                    'bpjs_kesehatan_no' => $employee->bpjs_kesehatan_no,
                    'jht_no' => $employee->jht_no,
                    'jp_no' => $employee->jp_no,
                    'jkk_no' => $employee->jkk_no,
                    'jkm_no' => $employee->jkm_no,
                ],
            ]
        ]);
    }

    /**
     * Update employee personal profile data and optional avatar image.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.max' => 'Nomor telepon maksimal 20 digit.',
            'gender.in' => 'Jenis kelamin harus male atau female.',
            'avatar.image' => 'Berkas foto profil harus berupa gambar.',
            'avatar.mimes' => 'Format foto profil harus berupa JPEG, PNG, atau JPG.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::transaction(function () use ($request, $user, $employee) {
            $user->name = $request->name;

            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            $user->save();

            $employee->update([
                'name' => $request->name,
                'phone' => $request->input('phone', $employee->phone),
                'gender' => $request->input('gender', $employee->gender),
                'birth_date' => $request->input('birth_date', $employee->birth_date),
                'address' => $request->input('address', $employee->address),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    /**
     * Change password for the authenticated employee.
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
