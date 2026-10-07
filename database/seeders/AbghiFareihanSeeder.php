<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\LeaveCategory;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\DailyReport;
use App\Models\DailyReportAttachment;
use App\Models\Agenda;
use App\Models\SwapPersonal;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Thr;
use App\Models\ThrItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AbghiFareihanSeeder extends Seeder
{
    /**
     * Run the database seeds for Abghi Fareihan (PT Goys Media).
     * Populates complete, realistic dummy data across all HRIS modules for client preview.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // 1. Dapatkan Perusahaan PT Goys Media & Akun Owner
            $company = Company::where('name_company', 'like', '%Goys%')->first();

            if (!$company) {
                $owner = User::firstOrCreate(
                    ['email' => 'hrd@goysmedia.com'],
                    [
                        'name' => 'HR Manager Goys',
                        'password' => Hash::make('password123'),
                        'role' => 'owner',
                    ]
                );

                $company = Company::create([
                    'user_id' => $owner->id,
                    'name_company' => 'PT Goys Media',
                    'email' => 'info@goysmedia.com',
                    'phone' => '081234567890',
                    'business_type' => 'Technology',
                    'province' => 'Jawa Barat',
                    'city' => 'Bandung',
                ]);
            } else {
                $owner = User::find($company->user_id) ?? User::where('role', 'owner')->first() ?? User::where('role', 'admin')->first();
            }

            $ownerId = $owner ? $owner->id : 1;

            // 2. Branch, Division, Position, Shift
            $branch = Branch::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Cabang Bandung'],
                [
                    'code' => 'CBG-BDO',
                    'address' => 'Jl. Asia Afrika No. 120, Kota Bandung, Jawa Barat',
                ]
            );

            $division = Division::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'IT'],
                []
            );

            $position = Position::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Lead Software Engineer / IT Manager'],
                []
            );

            $shift = Shift::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Shift Reguler IT'],
                [
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                ]
            );

            // Pastikan setting presensi aktif
            AttendanceSetting::firstOrCreate(
                ['company_id' => $company->id],
                [
                    'status_very_good' => 'Sangat Baik',
                    'status_good' => 'Baik',
                    'status_fair' => 'Cukup',
                    'status_poor' => 'Kurang',
                ]
            );

            // 3. Buat / Update Akun User Abghi Fareihan
            $user = User::updateOrCreate(
                ['email' => 'abghi@goysmedia.com'],
                [
                    'name' => 'Abghi Fareihan',
                    'password' => Hash::make('password123'),
                    'role' => 'employee',
                ]
            );

            // 4. Buat / Update Profil Karyawan Abghi Fareihan
            $employee = Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'name' => 'Abghi Fareihan',
                    'email' => 'abghi@goysmedia.com',
                    'phone' => '081234567899',
                    'gender' => 'male',
                    'nik' => '3273012304950001',
                    'nip' => 'GM-IT-001',
                    'branch_id' => $branch->id,
                    'division_id' => $division->id,
                    'position_id' => $position->id,
                    'joined_at' => '2023-01-10', // Karyawan tetap senior sejak 2023
                    'birth_date' => '1995-04-23',
                    'address' => 'Jl. Ir. H. Juanda No. 120, Dago, Coblong, Kota Bandung, Jawa Barat 40135',

                    // Data Bank
                    'bank_name' => 'BCA',
                    'bank_account_number' => '7820192831',
                    'bank_account_name' => 'Abghi Fareihan',

                    // Komponen Gaji & Tunjangan
                    'basic_salary' => 15000000, // Rp 15.000.000
                    'fixed_allowance' => 2500000, // Rp 2.500.000
                    'daily_allowance' => 75000,   // Rp 75.000 / hari
                    'other_allowance' => 500000,  // Rp 500.000
                    'overtime_rate_per_hour' => 75000,
                    'late_penalty_type' => 'flat',
                    'late_penalty_nominal' => 50000,
                    'alpha_penalty_type' => 'flat',
                    'alpha_penalty_nominal' => 200000,

                    // Data Pajak PPh 21
                    'npwp' => '81.234.567.8-428.000',
                    'ptkp_status' => 'K/1', // Kawin 1 Tanggungan
                    'taxable' => true,

                    // Nomor Kepesertaan BPJS
                    'bpjs_kesehatan_no' => '0001928374651',
                    'jht_no' => '120938475610',
                    'jp_no' => '130938475610',
                    'jkk_no' => '140938475610',
                    'jkm_no' => '150938475610',

                    'payroll_cycle' => 'monthly',
                    'is_active' => true,
                ]
            );

            // 5. Work Schedule (Jadwal Kerja September 2026)
            $startDate = Carbon::create(2026, 9, 1);
            $endDate = Carbon::create(2026, 9, 30);
            $schedulePeriod = CarbonPeriod::create($startDate, $endDate);

            foreach ($schedulePeriod as $curDate) {
                $curDateStr = $curDate->format('Y-m-d');
                $isWeekend = $curDate->isWeekend();

                WorkSchedule::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $curDateStr],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $isWeekend ? null : $shift->id,
                        'is_day_off' => $isWeekend,
                        'attendance_type' => 'all',
                        'allowed_branches' => json_encode([$branch->id]),
                    ]
                );
            }

            // 6. Kategori Cuti & Saldo Cuti (Leave Category & Leave Balance)
            $leaveCategories = [
                ['name' => 'Cuti Tahunan', 'quota' => 12, 'used' => 3],
                ['name' => 'Cuti Bersama', 'quota' => 5, 'used' => 1],
                ['name' => 'Cuti Khusus / Menikah', 'quota' => 3, 'used' => 0],
                ['name' => 'Izin Sakit', 'quota' => 10, 'used' => 1],
            ];

            $catIds = [];
            foreach ($leaveCategories as $catData) {
                $cat = LeaveCategory::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $catData['name']],
                    ['default_quota' => $catData['quota']]
                );
                $catIds[$catData['name']] = $cat->id;

                LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_category_id' => $cat->id,
                        'year' => 2026,
                    ],
                    [
                        'company_id' => $company->id,
                        'quota' => $catData['quota'],
                        'used' => $catData['used'],
                    ]
                );
            }

            // 7. Pengajuan Cuti (Leave Requests)
            // Approved: Liburan Tahunan (10-11 Sep 2026)
            LeaveRequest::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'start_date' => '2026-09-10',
                ],
                [
                    'leave_category_id' => $catIds['Cuti Tahunan'] ?? 1,
                    'end_date' => '2026-09-11',
                    'days_count' => 2,
                    'reason' => 'Liburan keluarga tahunan dan refreshing tim',
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-09-08 14:00:00',
                ]
            );

            // Approved: Izin Sakit (15 Jun 2026)
            LeaveRequest::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'start_date' => '2026-06-15',
                ],
                [
                    'leave_category_id' => $catIds['Izin Sakit'] ?? 1,
                    'end_date' => '2026-06-15',
                    'days_count' => 1,
                    'reason' => 'Pemeriksaan kesehatan rawat jalan dan istirahat dokter',
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-06-14 09:30:00',
                ]
            );

            // Pending: Cuti Bulan Depan (15-16 Okt 2026)
            LeaveRequest::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'start_date' => '2026-10-15',
                ],
                [
                    'leave_category_id' => $catIds['Cuti Tahunan'] ?? 1,
                    'end_date' => '2026-10-16',
                    'days_count' => 2,
                    'reason' => 'Acara syukuran keluarga besar di luar kota',
                    'status' => 'pending',
                ]
            );

            // Rejected: Izin Mendadak Masa Lalu
            LeaveRequest::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'start_date' => '2026-03-25',
                ],
                [
                    'leave_category_id' => $catIds['Cuti Tahunan'] ?? 1,
                    'end_date' => '2026-03-25',
                    'days_count' => 1,
                    'reason' => 'Keperluan mendadak urusan pribadi keluarga',
                    'status' => 'rejected',
                    'reject_reason' => 'Jadwal bertepatan dengan deadline rilis sistem HRIS Q1',
                    'approved_by' => $ownerId,
                ]
            );

            // 8. Data Kehadiran / Presensi Lengkap (Attendances) September 2026
            // Koordinat Kantor Cabang Bandung
            $officeLat = -6.917464;
            $officeLng = 107.619122;

            $attendanceDates = [
                '2026-09-01' => ['in' => '07:50:00', 'out' => '17:10:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-02' => ['in' => '07:48:00', 'out' => '17:15:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-03' => ['in' => '07:58:00', 'out' => '17:02:00', 'status' => 'good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-04' => ['in' => '07:45:00', 'out' => '20:30:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Hadir & lembur deployment release HRIS V2'],
                '2026-09-07' => ['in' => '08:15:00', 'out' => '17:05:00', 'status' => 'fair', 'late' => 15, 'early' => 0, 'notes' => 'Terlambat akibat kendala lalu lintas tol Pasteur'],
                '2026-09-08' => ['in' => '07:52:00', 'out' => '17:10:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-09' => ['in' => '07:55:00', 'out' => '17:00:00', 'status' => 'good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-10' => ['in' => null, 'out' => null, 'status' => 'leave', 'late' => 0, 'early' => 0, 'notes' => 'Cuti Tahunan Disetujui'],
                '2026-09-11' => ['in' => null, 'out' => null, 'status' => 'leave', 'late' => 0, 'early' => 0, 'notes' => 'Cuti Tahunan Disetujui'],
                '2026-09-14' => ['in' => '07:45:00', 'out' => '17:12:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-15' => ['in' => '07:50:00', 'out' => '17:05:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-16' => ['in' => '07:47:00', 'out' => '19:30:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Hadir & lembur optimasi database PostgreSQL'],
                '2026-09-17' => ['in' => '07:56:00', 'out' => '17:00:00', 'status' => 'good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-18' => ['in' => '07:50:00', 'out' => '17:15:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-21' => ['in' => '07:48:00', 'out' => '17:08:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-22' => ['in' => '07:53:00', 'out' => '17:10:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-23' => ['in' => '07:58:00', 'out' => '17:02:00', 'status' => 'good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-24' => ['in' => '07:49:00', 'out' => '17:14:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-25' => ['in' => '07:51:00', 'out' => '17:10:00', 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi reguler tepat waktu'],
                '2026-09-26' => ['in' => '07:52:00', 'out' => null, 'status' => 'very_good', 'late' => 0, 'early' => 0, 'notes' => 'Presensi masuk hari ini via Mobile App (Sedang Bekerja)'],
            ];

            foreach ($attendanceDates as $dateStr => $att) {
                $clockInTime = $att['in'] ? Carbon::parse("$dateStr {$att['in']}") : null;
                $clockOutTime = $att['out'] ? Carbon::parse("$dateStr {$att['out']}") : null;
                $totalWorkMinutes = ($clockInTime && $clockOutTime) ? (int) $clockInTime->diffInMinutes($clockOutTime) : ($clockInTime ? 480 : 0);

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $dateStr],
                    [
                        'company_id' => $company->id,
                        'shift_id' => ($att['status'] === 'leave') ? null : $shift->id,
                        'clock_in_time' => $clockInTime,
                        'clock_in_latitude' => $att['in'] ? $officeLat : null,
                        'clock_in_longitude' => $att['in'] ? $officeLng : null,
                        'clock_in_photo' => $att['in'] ? 'attendances/abghi_in_sample.jpg' : null,
                        'clock_out_time' => $clockOutTime,
                        'clock_out_latitude' => $att['out'] ? $officeLat : null,
                        'clock_out_longitude' => $att['out'] ? $officeLng : null,
                        'clock_out_photo' => $att['out'] ? 'attendances/abghi_out_sample.jpg' : null,
                        'late_minutes' => $att['late'],
                        'early_leaving_minutes' => $att['early'],
                        'total_violation_minutes' => $att['late'] + $att['early'],
                        'total_work_minutes' => $totalWorkMinutes,
                        'attendance_status' => $att['status'],
                        'notes' => $att['notes'],
                    ]
                );
            }

            // 9. Pengajuan Lembur (Overtimes)
            // Approved 1: 04 Sep 2026 (3 Jam)
            Overtime::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-04',
                ],
                [
                    'title' => 'Deploy Major Update HRIS Cloud V2',
                    'start_time' => '17:30:00',
                    'end_time' => '20:30:00',
                    'duration_minutes' => 180,
                    'description' => 'Deployment release fitur payroll prorata, kalkulasi PPh 21 TER, dan pengujian performa query',
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-09-04 21:00:00',
                ]
            );

            // Approved 2: 16 Sep 2026 (2 Jam)
            Overtime::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-16',
                ],
                [
                    'title' => 'Database Optimization & Security Patch',
                    'start_time' => '17:30:00',
                    'end_time' => '19:30:00',
                    'duration_minutes' => 120,
                    'description' => 'Optimasi query rekapitulasi kehadiran dan indexing database PostgreSQL untuk laporan eksekutif',
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-09-16 20:00:00',
                ]
            );

            // Pending: 25 Sep 2026 (2 Jam)
            Overtime::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-25',
                ],
                [
                    'title' => 'Persiapan UAT & Client Presentation HRIS',
                    'start_time' => '17:30:00',
                    'end_time' => '19:30:00',
                    'duration_minutes' => 120,
                    'description' => 'Menyiapkan database seeding lengkap dan showcase fitur employee portal untuk presentasi klien',
                    'status' => 'pending',
                ]
            );

            // Rejected: 14 Ags 2026
            Overtime::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-08-14',
                ],
                [
                    'title' => 'Maintenance Server Rutin Tambahan',
                    'start_time' => '17:00:00',
                    'end_time' => '19:00:00',
                    'duration_minutes' => 120,
                    'description' => 'Pekerjaan di luar lingkup jadwal maintenance rutin',
                    'status' => 'rejected',
                    'reject_reason' => 'Dapat dialihkan ke jadwal jam kerja normal berikutnya',
                    'approved_by' => $ownerId,
                ]
            );

            // 10. Klaim Pengeluaran / Reimbursement
            // Paid: Lisensi Server AWS
            Reimbursement::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-05',
                ],
                [
                    'amount' => 1850000,
                    'reason' => 'Perpanjangan Lisensi Server Cloud AWS & SSL Wildcard Domain PT Goys Media',
                    'attachment' => 'reimbursements/receipt_aws_cloud_goysmedia.pdf',
                    'status' => 'paid',
                    'payout_method' => 'direct',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-09-06 11:00:00',
                ]
            );

            // Approved: Medical Check Up
            Reimbursement::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-15',
                ],
                [
                    'amount' => 650000,
                    'reason' => 'Medical Check Up & Kacamata Kerja Tahunan Sesuai Kebijakan Benefit Perusahaan',
                    'attachment' => 'reimbursements/receipt_kacamata_optik_abghi.pdf',
                    'status' => 'approved',
                    'payout_method' => 'payroll',
                    'approved_by' => $ownerId,
                    'approved_at' => '2026-09-16 14:00:00',
                ]
            );

            // Pending: Biaya Transportasi & Tol
            Reimbursement::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-22',
                ],
                [
                    'amount' => 350000,
                    'reason' => 'Bensin & Tol Perjalanan Dinas Koordinasi Tim Teknis Cabang Jakarta',
                    'attachment' => 'reimbursements/receipt_tol_bensin_jkt.pdf',
                    'status' => 'pending',
                ]
            );

            // Rejected: Klaim Makan Internal
            Reimbursement::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-07-10',
                ],
                [
                    'amount' => 500000,
                    'reason' => 'Klaim Makan Malam Meeting Internal Tim IT',
                    'status' => 'rejected',
                    'reject_reason' => 'Klaim tidak melampirkan invoice resmi berstempel dan rincian item valid',
                    'approved_by' => $ownerId,
                ]
            );

            // 11. Pinjaman Karyawan / Kasbon (Loan & LoanInstallment)
            $loan = Loan::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-08-01',
                ],
                [
                    'amount' => 3000000,
                    'tenor' => 3,
                    'description' => 'Pinjaman Kasbon Karyawan - Renovasi Fasilitas Kerja WFH & Perangkat Kerja',
                    'status' => 'approved',
                ]
            );

            // Cicilan 1 (Paid - Agustus 2026)
            LoanInstallment::updateOrCreate(
                [
                    'loan_id' => $loan->id,
                    'due_date' => '2026-08-25',
                ],
                [
                    'amount' => 1000000,
                    'status' => 'paid',
                ]
            );

            // Cicilan 2 (Unpaid - September 2026)
            LoanInstallment::updateOrCreate(
                [
                    'loan_id' => $loan->id,
                    'due_date' => '2026-09-25',
                ],
                [
                    'amount' => 1000000,
                    'status' => 'unpaid',
                ]
            );

            // Cicilan 3 (Unpaid - Oktober 2026)
            LoanInstallment::updateOrCreate(
                [
                    'loan_id' => $loan->id,
                    'due_date' => '2026-10-25',
                ],
                [
                    'amount' => 1000000,
                    'status' => 'unpaid',
                ]
            );

            // 12. Laporan Kerja Harian (Daily Reports & Attachments)
            $dailyReports = [
                [
                    'date' => '2026-09-02',
                    'title' => 'Analisis Arsitektur Microservices & Integrasi Mobile API',
                    'description' => 'Melakukan benchmark endpoint API kehadiran mobile app dan implementasi cache token Sanctum untuk mengurangi query beban database.',
                    'attachment' => 'daily_reports/arch_review_microservices.pdf',
                ],
                [
                    'date' => '2026-09-08',
                    'title' => 'Optimalisasi Modul Payroll & Kalkulasi PPh 21 TER 2026',
                    'description' => 'Memverifikasi formula perhitungan TER A, B, C berdasarkan status PTKP karyawan serta potongan BPJS Ketenagakerjaan dan Kesehatan.',
                    'attachment' => 'daily_reports/payroll_testing_verification.pdf',
                ],
                [
                    'date' => '2026-09-15',
                    'title' => 'Review Keamanan & Audit Log Akses Karyawan',
                    'description' => 'Memeriksa sanitasi input, audit trail aktivitas pengguna role owner dan employee pada database serta hardening sistem.',
                    'attachment' => 'daily_reports/audit_security_hardening.pdf',
                ],
                [
                    'date' => '2026-09-22',
                    'title' => 'Penyusunan Dokumentasi Teknis API & Release Note V2',
                    'description' => 'Menyelesaikan OpenAPI Swagger documentation untuk tim frontend mobile dan web dashboard multi-tenant.',
                    'attachment' => null,
                ],
                [
                    'date' => '2026-09-25',
                    'title' => 'Persiapan Demo Preview Sistem HRIS untuk Klien Utama',
                    'description' => 'Memastikan seluruh dataset dummy, slip gaji, presensi GPS, pengajuan cuti, dan laporan lembur siap ditinjau secara komprehensif.',
                    'attachment' => 'daily_reports/demo_preview_checklist.pdf',
                ],
            ];

            foreach ($dailyReports as $repData) {
                $report = DailyReport::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $repData['date'],
                    ],
                    [
                        'title' => $repData['title'],
                        'description' => $repData['description'],
                        'created_at' => $repData['date'] . ' 16:30:00',
                        'updated_at' => $repData['date'] . ' 16:30:00',
                    ]
                );

                if (!empty($repData['attachment'])) {
                    DailyReportAttachment::firstOrCreate(
                        [
                            'daily_report_id' => $report->id,
                            'file_path' => $repData['attachment'],
                        ]
                    );
                }
            }

            // 13. Agenda Kerja (Agendas)
            $agendas = [
                [
                    'title' => 'Client Showcase & System Walkthrough PT Goys Media',
                    'date' => '2026-09-26',
                    'start_time' => '14:00:00',
                    'end_time' => '16:00:00',
                    'notes' => 'Presentasi langsung fitur HRIS Web dan Mobile kepada klien, mendemonstrasikan modul Presensi, Cuti, Lembur, dan Payroll.',
                    'is_priority' => true,
                ],
                [
                    'title' => 'Sprint Retrospective & IT Roadmap Q4 2026',
                    'date' => '2026-09-28',
                    'start_time' => '10:00:00',
                    'end_time' => '12:00:00',
                    'notes' => 'Evaluasi pencapaian fitur kuartal ketiga dan perencanaan pengembangan sistem kuartal keempat.',
                    'is_priority' => false,
                ],
                [
                    'title' => 'Monthly Management Review dengan HR Manager',
                    'date' => '2026-09-30',
                    'start_time' => '15:00:00',
                    'end_time' => '16:30:00',
                    'notes' => 'Review KPI tim IT dan penyerahan laporan bulanan operasional serta evaluasi kehadiran.',
                    'is_priority' => true,
                ],
            ];

            foreach ($agendas as $agData) {
                Agenda::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $agData['date'],
                        'title' => $agData['title'],
                    ],
                    [
                        'start_time' => $agData['start_time'],
                        'end_time' => $agData['end_time'],
                        'notes' => $agData['notes'],
                        'is_priority' => $agData['is_priority'],
                    ]
                );
            }

            // 14. Pertukaran Jadwal Kerja (Swap Personal)
            SwapPersonal::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'original_work_date' => '2026-09-18',
                ],
                [
                    'target_work_date' => '2026-09-19',
                    'reason' => 'Tukar jadwal kerja untuk pendampingan keluarga',
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                ]
            );

            // 15. Data THR (Tunjangan Hari Raya) Idul Fitri 2026
            $thr = Thr::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'THR-2026-FITRI',
                ],
                [
                    'year' => 2026,
                    'holiday_name' => 'Hari Raya Idul Fitri 1447 H',
                    'payment_date' => '2026-03-15',
                    'branch_id' => null,
                    'status' => 'paid',
                    'total_employees' => 1,
                    'total_amount' => 16625000,
                ]
            );

            ThrItem::updateOrCreate(
                [
                    'thr_id' => $thr->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'tenure_months' => 38,
                    'basis_amount' => 17500000,
                    'prorate_multiplier' => 1.00,
                    'thr_amount' => 17500000,
                    'tax_amount' => 875000,
                    'net_amount' => 16625000,
                ]
            );

            $thr->update([
                'total_employees' => $thr->items()->count(),
                'total_amount' => $thr->items()->sum('net_amount'),
            ]);

            DB::commit();

            $this->command->info('===============================================================');
            $this->command->info(' BERHASIL SEED DATA KARYAWAN: ABGHI FAREIHAN (PT GOYS MEDIA)');
            $this->command->info('===============================================================');
            $this->command->info(' Akun Login:');
            $this->command->info('   - Email    : abghi@goysmedia.com');
            $this->command->info('   - Password : password123');
            $this->command->info('   - Role     : employee');
            $this->command->info(' Profil Karyawan:');
            $this->command->info('   - Nama     : Abghi Fareihan');
            $this->command->info('   - NIP      : GM-IT-001');
            $this->command->info('   - Jabatan  : Lead Software Engineer / IT Manager');
            $this->command->info('   - Cabang   : Cabang Bandung');
            $this->command->info('   - Divisi   : IT');
            $this->command->info('   - Gaji     : Rp 15.000.000 + Tunjangan Rp 2.500.000');
            $this->command->info(' Data Terisi:');
            $this->command->info('   ✓ Profil Personal, NIK, NPWP, BPJS Kesehatan & Ketenagakerjaan');
            $this->command->info('   ✓ Data Bank BCA & Nomor Rekening');
            $this->command->info('   ✓ Jadwal Kerja (Work Schedules) 30 Hari September 2026');
            $this->command->info('   ✓ 20 Data Riwayat Presensi (Hadir, Sangat Baik, Lembur, Terlambat, Cuti)');
            $this->command->info('   ✓ 4 Kategori Saldo Cuti & 4 Riwayat Pengajuan Cuti (Approved, Pending, Rejected)');
            $this->command->info('   ✓ 4 Pengajuan Lembur (Approved 5 Jam, Pending, Rejected)');
            $this->command->info('   ✓ 4 Klaim Biaya Reimbursement (Paid AWS, Approved Medical, Pending, Rejected)');
            $this->command->info('   ✓ Pinjaman Kasbon Rp 3.000.000 dengan 3 Cicilan (1 Lunas, 2 Belum)');
            $this->command->info('   ✓ 5 Laporan Kerja Harian (Daily Reports) dengan File Attachment');
            $this->command->info('   ✓ 3 Agenda Kerja & Meeting Prioritas');
            $this->command->info('   ✓ 1 Riwayat Pertukaran Jadwal Kerja (Swap Schedule)');
            $this->command->info('   ✓ Slip Gaji Periode September 2026 dengan Rincian Komprehensif');
            $this->command->info('   ✓ Data THR Idul Fitri 2026 Penuh (Masa Kerja 38 Bulan)');
            $this->command->info('===============================================================');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Gagal melakukan seeding data Abghi Fareihan: ' . $e->getMessage());
            throw $e;
        }
    }
}
