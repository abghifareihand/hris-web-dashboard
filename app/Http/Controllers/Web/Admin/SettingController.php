<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => config('app.name', 'Frans HRIS'),
            'support_email' => 'support@franshris.com',
            'support_phone' => '+62 812-3456-7890',
            'trial_days' => 14,
            'maintenance_mode' => false,
            'allow_registration' => true,
            'default_currency' => 'IDR',
            'system_version' => 'v2.5.0-L13',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'support_email' => 'required|email',
            'support_phone' => 'required|string|max:20',
            'trial_days' => 'required|integer|min:1|max:90',
        ]);

        return back()->with('success', 'Pengaturan platform global berhasil diperbarui.');
    }
}
