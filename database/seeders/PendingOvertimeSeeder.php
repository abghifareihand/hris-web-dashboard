<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Overtime;
use Carbon\Carbon;

class PendingOvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari perusahaan aktif yang memiliki data karyawan
        $company = Company::where('name_company', 'like', '%Goys%')->first()
            ?? Company::whereHas('employees')->first()
            ?? Company::first();

        if (!$company) {
            $this->command->error('Tidak ada perusahaan ditemukan untuk seeding pengajuan lembur.');
            return;
        }

        $employees = Employee::where('company_id', $company->id)->get();
        if ($employees->isEmpty()) {
            $employees = Employee::all();
        }

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada karyawan ditemukan untuk seeding pengajuan lembur.');
            return;
        }

        // 12 data pengajuan lembur pending yang realistis
        $pendingOvertimesData = [
            [
                'title' => 'Penyelesaian Modul Rekonsiliasi Bank',
                'date' => '2026-10-06',
                'start_time' => '17:30:00',
                'end_time' => '20:30:00',
                'duration_minutes' => 180,
                'description' => 'Finalisasi bug fix modul rekonsiliasi kas dan bank sebelum closing bulanan finance.',
            ],
            [
                'title' => 'Migrasi Server & Deployment Database',
                'date' => '2026-10-05',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',
                'duration_minutes' => 180,
                'description' => 'Pelaksanaan migrasi database ke kluster baru dan verifikasi integritas backup data berkala.',
            ],
            [
                'title' => 'Stock Opname & Audit Inventaris Q3',
                'date' => '2026-10-05',
                'start_time' => '17:00:00',
                'end_time' => '21:00:00',
                'duration_minutes' => 240,
                'description' => 'Penghitungan fisik aset peralatan kantor dan rekonsiliasi data inventaris triwulanan.',
            ],
            [
                'title' => 'Penyusunan Laporan SPT Masa PPh 21',
                'date' => '2026-10-04',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'duration_minutes' => 120,
                'description' => 'Rekapitulasi bukti potong PPh 21 TER dan validasi data e-bupot sebelum batas pelaporan pajak.',
            ],
            [
                'title' => 'Troubleshooting Jaringan & Firewall Kantor',
                'date' => '2026-10-04',
                'start_time' => '18:00:00',
                'end_time' => '20:00:00',
                'duration_minutes' => 120,
                'description' => 'Perbaikan router core dan konfigurasi QoS untuk kelancaran koneksi teleconference cabang.',
            ],
            [
                'title' => 'Sprint Review & Bug Squashing Patch V2.4',
                'date' => '2026-10-03',
                'start_time' => '17:30:00',
                'end_time' => '20:00:00',
                'duration_minutes' => 150,
                'description' => 'Penyelesaian backlog prioritas tinggi jelang rilis update HRIS aplikasi web dan mobile.',
            ],
            [
                'title' => 'Pemberkasan Dokumen Tender Klien Baru',
                'date' => '2026-10-03',
                'start_time' => '17:00:00',
                'end_time' => '20:00:00',
                'duration_minutes' => 180,
                'description' => 'Penyusunan berkas administrasi dan proposal teknis lelang tender proyek skala nasional.',
            ],
            [
                'title' => 'Customer Onboarding & Pelatihan Klien',
                'date' => '2026-10-02',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'duration_minutes' => 120,
                'description' => 'Sesi asistensi teknis tambahan dan setup data master karyawan untuk 2 entitas klien baru.',
            ],
            [
                'title' => 'Pembuatan Materi Kampanye Rekrutmen Q4',
                'date' => '2026-10-02',
                'start_time' => '17:00:00',
                'end_time' => '19:00:00',
                'duration_minutes' => 120,
                'description' => 'Desain visual banner lowongan kerja dan konfigurasi kampanye iklan LinkedIn & Jobstreet.',
            ],
            [
                'title' => 'Audit Keamanan Aplikasi & Penetration Testing',
                'date' => '2026-10-01',
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
                'duration_minutes' => 240,
                'description' => 'Penetrasi testing endpoint API publik dan mitigasi kerentanan keamanan sebelum peluncuran fitur baru.',
            ],
            [
                'title' => 'Rekapitulasi Klaim Asuransi Karyawan',
                'date' => '2026-10-01',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'duration_minutes' => 120,
                'description' => 'Verifikasi dokumen klaim rawat inap dan kuitansi rumah sakit karyawan untuk diajukan ke asuransi.',
            ],
            [
                'title' => 'Persiapan Ruang & Materi Rapat Direksi',
                'date' => '2026-09-30',
                'start_time' => '17:00:00',
                'end_time' => '19:30:00',
                'duration_minutes' => 150,
                'description' => 'Finalisasi deck presentasi kinerja bisnis bulanan dan penataan fasilitas meeting eksekutif.',
            ],
        ];

        $empCount = $employees->count();

        foreach ($pendingOvertimesData as $index => $item) {
            $employee = $employees->get($index % $empCount);

            Overtime::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => $item['date'],
                    'title' => $item['title'],
                ],
                [
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'duration_minutes' => $item['duration_minutes'],
                    'description' => $item['description'],
                    'status' => 'pending',
                    'reject_reason' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );
        }

        $this->command->info('Berhasil menambahkan 12 data pengajuan lembur menunggu persetujuan (pending).');
    }
}
