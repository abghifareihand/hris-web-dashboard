<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyReport;
use App\Models\DailyReportAttachment;
use App\Models\Employee;

class DailyReportSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::where('company_id', 2)->with('user')->get();
        if ($employees->isEmpty()) return;

        $reportsData = [
            [
                'title' => 'Monitoring dan Pemeliharaan Server Production',
                'description' => 'Melakukan pengecekan rutin utilisasi CPU, memori, dan storage server HRIS. Semua service berjalan normal tanpa kendala.',
                'date' => '2026-09-02',
            ],
            [
                'title' => 'Penyusunan Rincian Gaji & Rekonsiliasi Pajak PPh 21',
                'description' => 'Mempersiapkan data rekapitulasi penggajian karyawan periode September beserta potongan BPJS Ketenagakerjaan & BPJS Kesehatan.',
                'date' => '2026-09-04',
            ],
            [
                'title' => 'Implementasi Fitur Rekapitulasi Absensi & Lembur',
                'description' => 'Menyelesaikan modul laporan rekap kehadiran dan rincian lembur karyawan pada portal web owner dengan export Excel.',
                'date' => '2026-09-08',
            ],
            [
                'title' => 'Koordinasi Penyesuaian Shift Kerja Cabang Bandung',
                'description' => 'Melakukan koordinasi dengan supervisor cabang Bandung mengenai rotasi shift pagi dan siang minggu depan.',
                'date' => '2026-09-10',
            ],
            [
                'title' => 'Review Laporan Performa Karyawan Bulanan',
                'description' => 'Mengevaluasi skor kepatuhan jam kerja, tingkat kehadiran, dan efisiensi waktu kerja bersih tim operasional.',
                'date' => '2026-09-12',
            ],
            [
                'title' => 'Verifikasi Pengajuan Reimbursement Medis & Transport',
                'description' => 'Memeriksa berkas klaim pengobatan rawat jalan dan nota bensin dinas dari 4 karyawan cabang Depok.',
                'date' => '2026-09-14',
            ],
        ];

        foreach ($reportsData as $idx => $data) {
            $emp = $employees[$idx % $employees->count()];
            if (!$emp->user_id) continue;

            $report = DailyReport::updateOrCreate(
                [
                    'user_id' => $emp->user_id,
                    'date' => $data['date'],
                    'title' => $data['title'],
                ],
                [
                    'description' => $data['description'],
                    'created_at' => $data['date'] . ' 16:30:00',
                    'updated_at' => $data['date'] . ' 16:30:00',
                ]
            );

            // Add dummy attachment for some reports
            if ($idx % 2 === 0) {
                DailyReportAttachment::firstOrCreate(
                    [
                        'daily_report_id' => $report->id,
                        'file_path' => 'daily_reports/sample_doc_' . $report->id . '.pdf',
                    ]
                );
            }
        }
    }
}
