<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\CompanyLoanSetting;
use App\Models\CompanyPayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Get all company settings in a single payload (optional summary).
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Semua pengaturan perusahaan berhasil diambil.',
            'data' => [
                'attendance' => $this->formatAttendanceSetting($this->getOrCreateAttendanceSetting($company->id)),
                'loan' => $this->formatLoanSetting($this->getOrCreateLoanSetting($company->id)),
                'payroll' => $this->formatPayrollSetting($this->getOrCreatePayrollSetting($company->id)),
            ],
        ]);
    }

    /**
     * Get attendance settings.
     */
    public function getAttendance(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $setting = $this->getOrCreateAttendanceSetting($company->id);

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan absensi berhasil diambil.',
            'data' => $this->formatAttendanceSetting($setting),
        ]);
    }

    /**
     * Update attendance settings.
     */
    public function updateAttendance(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'early_clock_in_minutes' => 'nullable|integer|min:0|max:240',
            'camera_mode' => 'required|in:both,front,back',
            'status_very_good' => 'required|string|max:50',
            'status_good' => 'required|string|max:50',
            'status_fair' => 'required|string|max:50',
            'status_poor' => 'required|string|max:50',
        ], [
            'late_tolerance_minutes.required' => 'Batas toleransi keterlambatan wajib diisi.',
            'late_tolerance_minutes.integer' => 'Batas toleransi keterlambatan harus berupa angka (0 - 120 menit).',
            'camera_mode.required' => 'Mode kamera absensi wajib dipilih.',
            'camera_mode.in' => 'Pilihan mode kamera tidak valid (front, back, both).',
            'status_very_good.required' => 'Label status Sangat Baik wajib diisi.',
            'status_good.required' => 'Label status Baik wajib diisi.',
            'status_fair.required' => 'Label status Cukup wajib diisi.',
            'status_poor.required' => 'Label status Kurang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = $this->getOrCreateAttendanceSetting($company->id);
        $setting->late_tolerance_minutes = (int) $request->late_tolerance_minutes;
        if ($request->filled('early_clock_in_minutes')) {
            $setting->early_clock_in_minutes = (int) $request->early_clock_in_minutes;
        }
        $setting->camera_mode = $request->camera_mode;
        $setting->status_very_good = $request->status_very_good;
        $setting->status_good = $request->status_good;
        $setting->status_fair = $request->status_fair;
        $setting->status_poor = $request->status_poor;
        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan absensi berhasil diperbarui.',
            'data' => $this->formatAttendanceSetting($setting),
        ]);
    }

    /**
     * Get loan/kasbon settings.
     */
    public function getLoan(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $setting = $this->getOrCreateLoanSetting($company->id);

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan pinjaman (kasbon) berhasil diambil.',
            'data' => $this->formatLoanSetting($setting),
        ]);
    }

    /**
     * Update loan/kasbon settings.
     */
    public function updateLoan(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'max_loan_amount' => 'nullable',
            'max_tenor_months' => 'nullable|integer|min:1|max:60',
            'due_date_day' => 'nullable|integer|min:1|max:30',
        ], [
            'max_tenor_months.integer' => 'Batas tenor harus berupa angka (1 - 60 bulan).',
            'due_date_day.integer' => 'Tanggal jatuh tempo harus berupa angka (1 - 30).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $maxLoanAmount = null;
        if ($request->filled('max_loan_amount')) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $request->max_loan_amount);
            $maxLoanAmount = $cleaned !== '' ? (int) $cleaned : null;
        }

        $maxTenorMonths = $request->filled('max_tenor_months') ? (int) $request->max_tenor_months : null;
        $dueDateDay = $request->filled('due_date_day') ? (int) $request->due_date_day : null;

        $setting = CompanyLoanSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'max_loan_amount' => $maxLoanAmount,
                'max_tenor_months' => $maxTenorMonths,
                'due_date_day' => $dueDateDay,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan pinjaman (kasbon) berhasil diperbarui.',
            'data' => $this->formatLoanSetting($setting),
        ]);
    }

    /**
     * Get payroll settings.
     */
    public function getPayroll(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $setting = $this->getOrCreatePayrollSetting($company->id);

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan kebijakan penggajian berhasil diambil.',
            'data' => $this->formatPayrollSetting($setting),
        ]);
    }

    /**
     * Update payroll settings.
     */
    public function updatePayroll(Request $request)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'default_payroll_cycle' => 'required|in:monthly,weekly,daily',
            'monthly_working_days' => 'required|integer|min:1|max:31',
            'weekly_working_days' => 'required|integer|min:1|max:7',
            'notes' => 'nullable|string|max:500',
        ], [
            'default_payroll_cycle.required' => 'Siklus periode penggajian default wajib dipilih.',
            'default_payroll_cycle.in' => 'Siklus penggajian harus berupa monthly, weekly, atau daily.',
            'monthly_working_days.required' => 'Standar hari kerja bulanan wajib diisi.',
            'monthly_working_days.integer' => 'Standar hari kerja bulanan harus berupa angka (1 - 31 hari).',
            'weekly_working_days.required' => 'Standar hari kerja mingguan wajib diisi.',
            'weekly_working_days.integer' => 'Standar hari kerja mingguan harus berupa angka (1 - 7 hari).',
            'notes.max' => 'Catatan kebijakan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = CompanyPayrollSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'default_payroll_cycle' => $request->default_payroll_cycle,
                'monthly_working_days' => (int) $request->monthly_working_days,
                'weekly_working_days' => (int) $request->weekly_working_days,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan kebijakan penggajian berhasil diperbarui.',
            'data' => $this->formatPayrollSetting($setting),
        ]);
    }

    /**
     * Helper: get or create AttendanceSetting with defaults.
     */
    private function getOrCreateAttendanceSetting(int $companyId): AttendanceSetting
    {
        return AttendanceSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'late_tolerance_minutes' => 10,
                'early_clock_in_minutes' => 60,
                'camera_mode' => 'both',
                'status_very_good' => 'Sangat Baik',
                'status_good' => 'Baik',
                'status_fair' => 'Cukup',
                'status_poor' => 'Kurang',
            ]
        );
    }

    /**
     * Helper: get or create CompanyLoanSetting.
     */
    private function getOrCreateLoanSetting(int $companyId): CompanyLoanSetting
    {
        return CompanyLoanSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'max_loan_amount' => null,
                'max_tenor_months' => null,
                'due_date_day' => null,
            ]
        );
    }

    /**
     * Helper: get or create CompanyPayrollSetting with defaults.
     */
    private function getOrCreatePayrollSetting(int $companyId): CompanyPayrollSetting
    {
        return CompanyPayrollSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'default_payroll_cycle' => 'monthly',
                'monthly_working_days' => 25,
                'weekly_working_days' => 6,
                'notes' => null,
            ]
        );
    }

    /**
     * Format AttendanceSetting for Flutter response.
     */
    private function formatAttendanceSetting(AttendanceSetting $setting): array
    {
        $cameraLabels = [
            'both' => 'Bebas (Depan / Belakang)',
            'front' => 'Kamera Depan Saja',
            'back' => 'Kamera Belakang Saja',
        ];

        return [
            'id' => $setting->id,
            'company_id' => $setting->company_id,
            'late_tolerance_minutes' => (int) $setting->late_tolerance_minutes,
            'early_clock_in_minutes' => (int) ($setting->early_clock_in_minutes ?? 60),
            'camera_mode' => $setting->camera_mode ?? 'both',
            'camera_mode_label' => $cameraLabels[$setting->camera_mode] ?? 'Bebas (Depan / Belakang)',
            'status_very_good' => $setting->status_very_good ?? 'Sangat Baik',
            'status_good' => $setting->status_good ?? 'Baik',
            'status_fair' => $setting->status_fair ?? 'Cukup',
            'status_poor' => $setting->status_poor ?? 'Kurang',
            'updated_at' => $setting->updated_at ? $setting->updated_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * Format CompanyLoanSetting for Flutter response.
     */
    private function formatLoanSetting(CompanyLoanSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'company_id' => $setting->company_id,
            'max_loan_amount' => $setting->max_loan_amount !== null ? (int) $setting->max_loan_amount : null,
            'max_loan_amount_formatted' => $setting->max_loan_amount !== null
                ? 'Rp ' . number_format($setting->max_loan_amount, 0, ',', '.')
                : 'Tidak Terbatas',
            'max_tenor_months' => $setting->max_tenor_months !== null ? (int) $setting->max_tenor_months : null,
            'max_tenor_months_label' => $setting->max_tenor_months !== null
                ? "{$setting->max_tenor_months} Bulan"
                : 'Tidak Terbatas',
            'due_date_day' => $setting->due_date_day !== null ? (int) $setting->due_date_day : null,
            'due_date_day_label' => $setting->due_date_day !== null
                ? "Tanggal {$setting->due_date_day} setiap bulan"
                : 'Tanggal 1 setiap bulan (Default)',
            'updated_at' => $setting->updated_at ? $setting->updated_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * Format CompanyPayrollSetting for Flutter response.
     */
    private function formatPayrollSetting(CompanyPayrollSetting $setting): array
    {
        $cycleLabels = [
            'monthly' => 'Bulanan',
            'weekly' => 'Mingguan',
            'daily' => 'Harian',
        ];

        return [
            'id' => $setting->id,
            'company_id' => $setting->company_id,
            'default_payroll_cycle' => $setting->default_payroll_cycle ?? 'monthly',
            'default_payroll_cycle_label' => $cycleLabels[$setting->default_payroll_cycle] ?? 'Bulanan',
            'monthly_working_days' => (int) ($setting->monthly_working_days ?? 25),
            'weekly_working_days' => (int) ($setting->weekly_working_days ?? 6),
            'notes' => $setting->notes,
            'updated_at' => $setting->updated_at ? $setting->updated_at->format('Y-m-d H:i') : null,
        ];
    }
}
