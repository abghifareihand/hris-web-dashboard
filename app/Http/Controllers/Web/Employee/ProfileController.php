<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the employee profile overview.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee()
            ->with(['branch', 'division', 'position', 'company'])
            ->first();

        return Inertia::render('Employee/Profile/Index', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    /**
     * Display the edit profile form.
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee()
            ->with(['branch', 'division', 'position', 'company'])
            ->first();

        return Inertia::render('Employee/Profile/Edit', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    /**
     * Update employee personal profile, contact, bank, and avatar.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        if (!$employee) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'avatar.image' => 'Berkas foto profil harus berupa gambar.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        DB::transaction(function () use ($request, $user, $employee) {
            $user->name = $request->name;

            if ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            $user->save();

            $employee->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
            ]);
        });

        return redirect()->route('employee.profile.index')
            ->with('success', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Display the dedicated change password page.
     */
    public function password(Request $request)
    {
        return Inertia::render('Employee/Profile/Password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Change the authenticated employee password.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('employee.profile.password')
            ->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }
}
