<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class PendingLeaveSeeder extends Seeder
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
            $this->command->error('Tidak ada perusahaan ditemukan untuk seeding pengajuan cuti.');
            return;
        }

        $employees = Employee::where('company_id', $company->id)->get();
        if ($employees->isEmpty()) {
            $employees = Employee::all();
        }

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada karyawan ditemukan untuk seeding pengajuan cuti.');
            return;
        }

        // Ambil atau siapkan kategori cuti untuk perusahaan ini
        $categories = LeaveCategory::where('company_id', $company->id)->pluck('id', 'name')->toArray();

        $tahunanId = $categories['Cuti Tahunan'] ?? LeaveCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Cuti Tahunan'])->id;
        $sakitId = $categories['Izin Sakit'] ?? LeaveCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Izin Sakit'])->id;
        $bersamaId = $categories['Cuti Bersama'] ?? LeaveCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Cuti Bersama'])->id;
        $khususId = $categories['Cuti Khusus / Menikah'] ?? LeaveCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Cuti Khusus / Menikah'])->id;

        // Daftar 12 pengajuan cuti pending yang realistis
        $pendingLeavesData = [
            [
                'category_id' => $tahunanId,
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-13',
                'days_count' => 2,
                'reason' => 'Menghadiri acara pernikahan adik kandung di luar kota (Yogyakarta)',
            ],
            [
                'category_id' => $sakitId,
                'start_date' => '2026-10-07',
                'end_date' => '2026-10-08',
                'days_count' => 2,
                'reason' => 'Demam tinggi dan flu berat, istirahat sesuai anjuran dan resep dokter',
            ],
            [
                'category_id' => $tahunanId,
                'start_date' => '2026-10-14',
                'end_date' => '2026-10-14',
                'days_count' => 1,
                'reason' => 'Keperluan keluarga mendesak dan renovasi instalasi air rumah tinggal',
            ],
            [
                'category_id' => $sakitId,
                'start_date' => '2026-10-09',
                'end_date' => '2026-10-09',
                'days_count' => 1,
                'reason' => 'Pemeriksaan kesehatan rawat jalan rutin dan medical check-up tahunan',
            ],
            [
                'category_id' => $khususId,
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-17',
                'days_count' => 3,
                'reason' => 'Mendampingi istri melahirkan dan merawat bayi pasca persalinan',
            ],
            [
                'category_id' => $tahunanId,
                'start_date' => '2026-10-16',
                'end_date' => '2026-10-16',
                'days_count' => 1,
                'reason' => 'Urusan administrasi perpajakan dan perpanjangan paspor di kantor imigrasi',
            ],
            [
                'category_id' => $khususId,
                'start_date' => '2026-10-19',
                'end_date' => '2026-10-20',
                'days_count' => 2,
                'reason' => 'Menghadiri upacara adat pemakaman anggota keluarga besar di Solo',
            ],
            [
                'category_id' => $sakitId,
                'start_date' => '2026-10-21',
                'end_date' => '2026-10-22',
                'days_count' => 2,
                'reason' => 'Istirahat dan masa pemulihan pasca tindakan medis cabut gigi bungsu di RS',
            ],
            [
                'category_id' => $tahunanId,
                'start_date' => '2026-10-22',
                'end_date' => '2026-10-23',
                'days_count' => 2,
                'reason' => 'Liburan keluarga tahunan dan refreshing tim menyambut akhir pekan panjang',
            ],
            [
                'category_id' => $tahunanId,
                'start_date' => '2026-10-26',
                'end_date' => '2026-10-27',
                'days_count' => 2,
                'reason' => 'Menghadiri acara wisuda kelulusan sarjana adik kandung di Semarang',
            ],
            [
                'category_id' => $bersamaId,
                'start_date' => '2026-10-28',
                'end_date' => '2026-10-28',
                'days_count' => 1,
                'reason' => 'Perjalanan ziarah keluarga besar ke makam leluhur di Jawa Timur',
            ],
            [
                'category_id' => $sakitId,
                'start_date' => '2026-10-29',
                'end_date' => '2026-10-29',
                'days_count' => 1,
                'reason' => 'Pemeriksaan lanjutan mata dan perawatan rawat jalan dokter spesialis',
            ],
        ];

        $empCount = $employees->count();

        foreach ($pendingLeavesData as $index => $item) {
            $employee = $employees->get($index % $empCount);

            LeaveRequest::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'start_date' => $item['start_date'],
                ],
                [
                    'leave_category_id' => $item['category_id'],
                    'end_date' => $item['end_date'],
                    'days_count' => $item['days_count'],
                    'reason' => $item['reason'],
                    'attachment' => null,
                    'status' => 'pending',
                    'reject_reason' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );
        }

        $this->command->info('Berhasil menambahkan 12 data pengajuan cuti menunggu persetujuan (pending).');
    }
}
