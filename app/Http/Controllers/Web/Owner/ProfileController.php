<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        return Inertia::render('Owner/Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar ? (str_starts_with($user->avatar, 'http') ? $user->avatar : Storage::url($user->avatar)) : null,
                'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name_company ?? $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo ? (str_starts_with($company->logo, 'http') ? $company->logo : Storage::url($company->logo)) : null,
            ] : null,
        ]);
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        return Inertia::render('Owner/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar ? (str_starts_with($user->avatar, 'http') ? $user->avatar : Storage::url($user->avatar)) : null,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name_company ?? $company->name,
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'avatar.image' => 'Berkas foto profil harus berupa gambar.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('user-avatars', 'public');
        }

        $user->update($validated);

        return redirect()->route('owner.profile.index')
            ->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function passwordView(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Owner/Profile/Password', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('owner.profile.index')
            ->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
