<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Division;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('id', 2)->first() ?? Company::first();

        if (!$company) {
            return;
        }

        $branchJakarta = Branch::where('company_id', $company->id)->where('name', 'like', '%Jakarta%')->first() 
            ?? Branch::where('company_id', $company->id)->first();
        $branchBandung = Branch::where('company_id', $company->id)->where('name', 'like', '%Bandung%')->first();
        $divIt = Division::where('company_id', $company->id)->where('name', 'like', '%IT%')->first() 
            ?? Division::where('company_id', $company->id)->first();
        $divHr = Division::where('company_id', $company->id)->where('name', 'like', '%HR%')->first();

        $announcements = [
            [
                'company_id' => $company->id,
                'branch_id' => null, // Semua cabang
                'division_id' => null, // Semua divisi
                'title' => 'Pemberitahuan Hari Libur Nasional Maulid Nabi & Penyesuaian Shift',
                'content' => "Diberitahukan kepada seluruh karyawan PT Goys Media bahwa dalam rangka Hari Libur Nasional Maulid Nabi Muhammad SAW yang jatuh pada tanggal 16 September 2026, operasional kantor pusat dan kantor cabang diliburkan.\n\nBagi divisi operasional dan customer care yang bertugas pada sistem shift terjadwal, penyesuaian jadwal dan insentif kehadiran lembur hari raya telah dikoordinasikan oleh masing-masing supervisor divisi.\n\nSelamat berlibur dan berkumpul bersama keluarga tercinta!",
                'start_date' => Carbon::now()->subDays(2)->toDateString(),
                'end_date' => Carbon::now()->addDays(5)->toDateString(),
                'is_active' => true,
            ],
            [
                'company_id' => $company->id,
                'branch_id' => $branchJakarta?->id,
                'division_id' => null,
                'title' => 'Pemeliharaan Jaringan Listrik & Internet Gedung Cabang Jakarta',
                'content' => "Diinformasikan kepada seluruh staf yang berkantor di Cabang Jakarta, akan diadakan maintenance infrastruktur kelistrikan dan jaringan internet pada hari Sabtu mendatang pukul 08:00 - 14:00 WIB.\n\nMohon pastikan seluruh perangkat kerja (PC, server lokal) telah dimatikan sebelum hari Jumat sore. Pekerjaan remote (WFA) diperbolehkan bagi divisi yang membutuhkan koneksi darurat.",
                'start_date' => Carbon::now()->subDay()->toDateString(),
                'end_date' => Carbon::now()->addDays(3)->toDateString(),
                'is_active' => true,
            ],
            [
                'company_id' => $company->id,
                'branch_id' => null,
                'division_id' => $divIt?->id,
                'title' => 'Sosialisasi Standar Baru Deployment & Security Patching Q4',
                'content' => "Kepada seluruh tim Divisi IT dan Engineering,\n\nBriefing teknis mengenai implementasi CI/CD pipeline baru dan standar audit keamanan perangkat akan diselenggarakan pada hari Selasa pukul 10:00 WIB via Google Meet.\n\nKehadiran bersifat wajib bagi seluruh pengembang dan technical lead.",
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'is_active' => true,
            ],
            [
                'company_id' => $company->id,
                'branch_id' => $branchBandung?->id ?? $branchJakarta?->id,
                'division_id' => null,
                'title' => 'Jadwal Medical Check Up (MCU) Tahunan Karyawan',
                'content' => "Dalam rangka menjaga kesehatan dan kebugaran seluruh karyawan, perusahaan menyelenggarakan program Medical Check Up (MCU) Tahunan yang bekerjasama dengan Prodia.\n\nPelaksanaan akan dimulai bulan depan. Formulir pendaftaran dan pemilihan paket pemeriksaan dapat diakses melalui HR Portal.",
                'start_date' => Carbon::now()->addDays(3)->toDateString(),
                'end_date' => Carbon::now()->addDays(20)->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::updateOrCreate(
                ['company_id' => $data['company_id'], 'title' => $data['title']],
                $data
            );
        }
    }
}
