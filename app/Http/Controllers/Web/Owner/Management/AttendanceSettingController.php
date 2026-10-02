<?php

namespace App\Http\Controllers\Web\Owner\Management;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceSettingController extends Controller
{
    /**
     * Display the attendance settings page.
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        
        $setting = AttendanceSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'late_tolerance_minutes' => 10,
                'camera_mode' => 'both',
                'status_very_good' => 'Sangat Baik',
                'status_good' => 'Baik',
                'status_fair' => 'Cukup',
                'status_poor' => 'Kurang',
            ]
        );

        return Inertia::render('Owner/Management/Attendance/Settings/Index', [
            'setting' => $setting,
        ]);
    }

    /**
     * Update the attendance settings.
     */
    public function update(Request $request)
    {
        $company = $request->user()->company;

        $request->validate([
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'camera_mode' => 'required|in:front,back,both',
            'status_very_good' => 'required|string|max:50',
            'status_good' => 'required|string|max:50',
            'status_fair' => 'required|string|max:50',
            'status_poor' => 'required|string|max:50',
        ]);

        $setting = AttendanceSetting::where('company_id', $company->id)->first();
        if (!$setting) {
            $setting = new AttendanceSetting();
            $setting->company_id = $company->id;
        }

        $setting->late_tolerance_minutes = $request->late_tolerance_minutes;
        $setting->camera_mode = $request->camera_mode;
        $setting->status_very_good = $request->status_very_good;
        $setting->status_good = $request->status_good;
        $setting->status_fair = $request->status_fair;
        $setting->status_poor = $request->status_poor;
        $setting->save();

        return redirect()->back()->with('success', 'Pengaturan absensi berhasil disimpan.');
    }

    /**
     * Update individual status label.
     */
    public function updateStatus(Request $request)
    {
        $company = $request->user()->company;

        $validated = $request->validate([
            'status_key' => 'required|in:status_very_good,status_good,status_fair,status_poor',
            'status_label' => 'required|string|max:50',
        ]);

        $setting = AttendanceSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'late_tolerance_minutes' => 10,
                'camera_mode' => 'both',
                'status_very_good' => 'Sangat Baik',
                'status_good' => 'Baik',
                'status_fair' => 'Cukup',
                'status_poor' => 'Kurang',
            ]
        );

        $key = $validated['status_key'];
        $setting->$key = $validated['status_label'];
        $setting->save();

        return redirect()->back()->with('success', 'Status absensi berhasil diperbarui.');
    }
}
