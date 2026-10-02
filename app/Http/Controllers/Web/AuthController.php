<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $request->session()->regenerate();

            if ($user->role === 'owner') {
                return redirect()->intended(route('owner.dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name);
            } elseif ($user->role === 'employee') {
                return redirect()->intended(route('employee.dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name);
            }

            Auth::logout();
            return back()->withErrors([
                'email' => 'Akun anda tidak memiliki akses ke portal ini.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('info', 'Anda telah berhasil keluar.');
    }
}
