<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter Tier',
                'badge' => 'UMKM & Startup',
                'price' => 299000,
                'period' => 'bulan',
                'max_employees' => 25,
                'features' => [
                    'Presensi Geofence GPS & Face Verification',
                    'Manajemen Shift & Kalender Kerja',
                    'Pengajuan Cuti & Saldo Tahunan',
                    'Slip Gaji Elektronik Otomatis',
                    'Maksimal 25 Karyawan',
                ],
                'is_popular' => false,
            ],
            [
                'id' => 'pro',
                'name' => 'Professional Growth',
                'badge' => 'Paling Populer',
                'price' => 599000,
                'period' => 'bulan',
                'max_employees' => 100,
                'features' => [
                    'Semua fitur paket Starter',
                    'Manajemen Lembur (Overtime Approval)',
                    'Klaim Reimbursement Operasional',
                    'Pinjaman / Kasbon Tenor Otomatis',
                    'Kalkulasi Pajak PPh 21 & BPJS',
                    'Laporan Kinerja & Log Aktivitas',
                    'Maksimal 100 Karyawan',
                ],
                'is_popular' => true,
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise Scale',
                'badge' => 'Korporasi Besar',
                'price' => 1499000,
                'period' => 'bulan',
                'max_employees' => 'Tak Terbatas',
                'features' => [
                    'Semua fitur paket Professional',
                    'Unlimited Karyawan & Multi-Cabang',
                    'Integrasi Mesin Fingerprint Biometrik API',
                    'Dedicated Account Manager 24/7',
                    'Custom Payroll Formula & Kompensasi',
                    'SLA Uptime 99.9% Bergaransi',
                ],
                'is_popular' => false,
            ],
        ];

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }
}
