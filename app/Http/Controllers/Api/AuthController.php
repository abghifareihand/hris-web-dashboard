<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Send dummy OTP (0000) for Registration or Password Reset.
     * Caches OTP for 5 minutes.
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'type' => 'nullable|string|in:register,reset_password',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'type.in' => 'Tipe OTP harus register atau reset_password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type', 'register');
        $email = $request->input('email');
        $userExists = User::where('email', $email)->exists();

        if ($type === 'register' && $userExists) {
            return response()->json([
                'status' => false,
                'message' => 'Email sudah terdaftar. Silakan gunakan email lain atau login.',
                'errors' => [
                    'email' => ['Email sudah digunakan.'],
                ],
            ], 422);
        }

        if ($type === 'reset_password' && !$userExists) {
            return response()->json([
                'status' => false,
                'message' => 'Email tidak ditemukan.',
                'errors' => [
                    'email' => ['Email belum terdaftar di sistem.'],
                ],
            ], 404);
        }

        // Dummy OTP generation (can be replaced with real random OTP + SMTP later)
        $otp = '0000';
        Cache::put("otp_{$type}_{$email}", $otp, now()->addMinutes(5));

        return response()->json([
            'status' => true,
            'message' => 'Kode OTP berhasil dikirim ke ' . $email . '. (Gunakan kode dummy: 0000)',
            'data' => [
                'email' => $email,
                'type' => $type,
                'expires_in_seconds' => 300,
            ],
        ]);
    }
    /**
     * Check if email is already registered before proceeding to OTP.
     */
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = User::where('email', $request->email)->exists();
        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Email sudah terdaftar. Silakan gunakan email lain atau login.',
                'errors' => [
                    'email' => ['Email sudah digunakan.'],
                ],
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Email tersedia untuk pendaftaran.',
        ]);
    }

    /**
     * Register owner account with dummy OTP verification (Step 1 -> OTP).
     * Returns Sanctum auth token immediately.
     */
    public function registerOwnerAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:25',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'nullable|string',
            'otp' => 'required|string',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'otp.required' => 'Kode OTP wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // OTP Verification from Cache (or fallback to dummy 0000)
        $cachedOtp = Cache::get("otp_register_{$request->email}");
        $isValidOtp = ($request->otp === '0000') || ($cachedOtp && $cachedOtp === $request->otp);

        if (!$isValidOtp) {
            return response()->json([
                'status' => false,
                'message' => 'Kode OTP salah atau telah kadaluarsa. Silakan masukkan 0000 atau kirim ulang kode.',
                'errors' => [
                    'otp' => ['Kode OTP tidak valid.'],
                ],
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'email_verified_at' => now(),
            ]);

            // Create Sanctum Token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Clear used OTP from cache
            Cache::forget("otp_register_{$request->email}");

            return response()->json([
                'status' => true,
                'message' => 'Registrasi akun owner berhasil.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'company' => null,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat membuat akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function registerOwner(Request $request)
    {
        $request->validate([
            // STEP 1 (Owner info)
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'nullable|string',

            // STEP 2 (Company info)
            'logo' => 'nullable|image|max:2048',
            'name_company' => 'required|string|max:255',
            'email_company' => 'required|string|email|max:255|unique:companies,email',
            'phone_company' => 'required|string|max:25',
            'business_type' => 'required|string',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Create User (Owner role)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
            ]);

            // Handle logo upload
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('company_logos', 'public');
            }

            // Create Company
            $company = Company::create([
                'user_id' => $user->id,
                'name_company' => $request->name_company,
                'email' => $request->email_company,
                'phone' => $request->phone_company,
                'business_type' => $request->business_type,
                'province' => $request->province,
                'city' => $request->city,
                'logo' => $logoPath,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registrasi owner & perusahaan berhasil.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function registerEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_id' => 'nullable|exists:positions,id',
        ]);

        try {
            DB::beginTransaction();

            // Create Pending Employee registration
            $pendingEmployee = \App\Models\PendingEmployee::create([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id,
                'division_id' => $request->division_id,
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'status' => 'pending',
            ]);

            // Send Notification to Company Owner
            $company = \App\Models\Company::find($request->company_id);
            if ($company && $company->owner) {
                $company->owner->notify(new \App\Notifications\NewEmployeeRegisterPendingRequest($pendingEmployee));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registrasi berhasil. Silakan tunggu persetujuan dari pihak perusahaan.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if ($request->filled('fcm_token')) {
            $user->update([
                'fcm_token' => $request->fcm_token,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'web_dashboard_url' => rtrim(config('app.url') ?: $request->root(), '/') . '/login',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Detail user berhasil diambil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'company' => $user->company,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    public function profileOwner(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'owner') {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Anda bukan company.',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profil company berhasil diambil.',
            'profile' => $user->load('company'),
        ]);
    }

    public function profileEmployee(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'employee') {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Anda bukan employee.',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profil employee berhasil diambil.',
            'profile' => $user->load('employee.company'),
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FCM token berhasil disimpan.',
            'data' => [
                'user_id' => $user->id,
                'fcm_token' => $user->fcm_token,
            ],
        ]);
    }
}

