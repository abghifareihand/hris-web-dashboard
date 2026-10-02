<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $userLoad = match($user->role) {
            'company' => $user->load('company'),
            'employee' => $user->load('employee.company'),
            default => $user,
        };

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'user' => $userLoad,
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
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
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
}
