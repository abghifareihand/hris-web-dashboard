<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\DailyReport;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Shift;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeDataSeeder extends Seeder
{
    /**
     * Seeder khusus untuk menguji pagination di UI Mobile Flutter (25-30 data per fitur).
     * Fokus pada karyawan: Abghi Fareihan (abghi@goysmedia.com)
     *
     * Cara menjalankan:
     * php artisan db:seed --class=EmployeeDataSeeder
     *
     * Jika sudah selesai testing, file ini bisa langsung dihapus atau jalankan:
     * php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $user = User::where('email', 'abghi@goysmedia.com')->first();
        if (!$user) {
            $this->command->error('User Abghi Fareihan (abghi@goysmedia.com) tidak ditemukan. Pastikan DatabaseSeeder sudah dijalankan.');
            return;
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            $this->command->error('Profil employee Abghi Fareihan tidak ditemukan.');
            return;
        }

        $company = Company::find($employee->company_id);
        $shift = Shift::where('company_id', $company->id)->first();
        $categories = LeaveCategory::where('company_id', $company->id)->get();
        $otherEmployees = Employee::where('company_id', $company->id)
            ->where('id', '!=', $employee->id)
            ->get();

        $today = Carbon::today();

        $this->command->info('Memulai seeding 25-30 data per fitur untuk: ' . $employee->name);

        // =========================================================================
        // 1. JADWAL KERJA (Work Schedules) - 30 Hari
        // =========================================================================
        for ($i = 1; $i <= 30; $i++) {
            $date = $today->copy()->subDays(30 - $i)->format('Y-m-d');
            $isWeekend = Carbon::parse($date)->isWeekend();

            WorkSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ],
                [
                    'company_id' => $company->id,
                    'shift_id' => $isWeekend ? null : ($shift ? $shift->id : null),
                    'is_day_off' => $isWeekend,
                    'attendance_type' => 'all',
                    'allowed_branches' => ['Kantor Pusat Jakarta', 'Cabang Bandung'],
                ]
            );
        }
        $this->command->info('✓ 30 Jadwal Kerja dibuat.');

        // =========================================================================
        // 2. KEHADIRAN / ABSENSI (Attendances) - 25 Data
        // =========================================================================
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(26 - $i)->format('Y-m-d');
            $isLate = ($i % 4 === 0);
            $clockInHour = $isLate ? '08:35:00' : '07:55:00';

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ],
                [
                    'company_id' => $company->id,
                    'shift_id' => $shift ? $shift->id : null,
                    'clock_in_time' => "$date $clockInHour",
                    'clock_in_latitude' => -6.200000,
                    'clock_in_longitude' => 106.816666,
                    'clock_in_address' => 'Jl. Jenderal Sudirman No. Kav 21, Karet Semanggi, Jakarta Selatan',
                    'clock_out_time' => "$date 17:05:00",
                    'clock_out_latitude' => -6.200000,
                    'clock_out_longitude' => 106.816666,
                    'clock_out_address' => 'Jl. Jenderal Sudirman No. Kav 21, Karet Semanggi, Jakarta Selatan',
                    'late_minutes' => $isLate ? 35 : 0,
                    'early_leaving_minutes' => 0,
                    'total_work_minutes' => $isLate ? 510 : 545,
                    'total_violation_minutes' => $isLate ? 35 : 0,
                    'attendance_status' => $isLate ? 'Late' : 'On Time',
                    'notes' => $isLate ? 'Terjebak macet jalan raya' : 'Hadir tepat waktu',
                ]
            );
        }
        $this->command->info('✓ 25 Riwayat Absensi dibuat.');

        // =========================================================================
        // 3. AGENDA KERJA (Agendas) - 25 Data
        // =========================================================================
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(15 - $i)->format('Y-m-d');
            Agenda::create([
                'user_id' => $user->id,
                'title' => "Agenda Kerja #$i - Sprint Review & Daily Standup",
                'date' => $date,
                'start_time' => '09:00:00',
                'end_time' => '10:30:00',
                'notes' => "Membahas progres modul fitur #$i dan sinkronisasi API mobile.",
                'is_priority' => ($i % 3 === 0),
            ]);
        }
        $this->command->info('✓ 25 Agenda dibuat.');

        // =========================================================================
        // 4. PENGAJUAN CUTI (Leave Requests) - 25 Data
        // =========================================================================
        $leaveStatuses = ['pending', 'approved', 'rejected'];
        for ($i = 1; $i <= 25; $i++) {
            $cat = $categories->isNotEmpty() ? $categories[$i % $categories->count()] : null;
            $startDate = $today->copy()->subDays(60 - ($i * 2))->format('Y-m-d');
            $endDate = Carbon::parse($startDate)->addDay()->format('Y-m-d');
            $status = $leaveStatuses[$i % count($leaveStatuses)];

            LeaveRequest::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_category_id' => $cat ? $cat->id : 1,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => 2,
                'reason' => "Permohonan cuti keperluan pribadi urusan keluarga #$i",
                'status' => $status,
                'reject_reason' => $status === 'rejected' ? 'Kuota tanggal padat' : null,
                'approved_by' => $status === 'approved' ? $company->user_id : null,
                'approved_at' => $status === 'approved' ? Carbon::parse($startDate)->subDays(3) : null,
            ]);
        }
        $this->command->info('✓ 25 Pengajuan Cuti dibuat.');

        // =========================================================================
        // 5. PENGAJUAN LEMBUR (Overtimes) - 25 Data
        // =========================================================================
        $otStatuses = ['pending', 'approved', 'rejected'];
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(30 - $i)->format('Y-m-d');
            $status = $otStatuses[$i % count($otStatuses)];

            Overtime::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'title' => "Lembur Deployment & Hotfix #$i",
                'date' => $date,
                'start_time' => '17:30:00',
                'end_time' => '20:30:00',
                'duration_minutes' => 180,
                'description' => "Menyelesaikan perbaikan backend dan optimasi endpoint #$i",
                'status' => $status,
                'reject_reason' => $status === 'rejected' ? 'Bukan jam kerja kritis' : null,
                'approved_by' => $status === 'approved' ? $company->user_id : null,
                'approved_at' => $status === 'approved' ? Carbon::parse($date)->addDay() : null,
            ]);
        }
        $this->command->info('✓ 25 Pengajuan Lembur dibuat.');

        // =========================================================================
        // 6. TUKAR JADWAL PRIBADI (Swap Personal) - 25 Data
        // =========================================================================
        $swapStatuses = ['pending', 'approved', 'rejected'];
        for ($i = 1; $i <= 25; $i++) {
            $origDate = $today->copy()->subDays(40 - $i)->format('Y-m-d');
            $targetDate = Carbon::parse($origDate)->addDays(3)->format('Y-m-d');
            $status = $swapStatuses[$i % count($swapStatuses)];

            SwapPersonal::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'original_work_date' => $origDate,
                'target_work_date' => $targetDate,
                'reason' => "Ada keperluan mendadak tanggal $origDate sehingga mohon ditukar ke $targetDate (Permohonan #$i)",
                'status' => $status,
                'approved_by' => ($status === 'approved' || $status === 'rejected') ? $company->user_id : null,
            ]);
        }
        $this->command->info('✓ 25 Tukar Jadwal Pribadi dibuat.');

        // =========================================================================
        // 7. TUKAR JADWAL TIM (Swap Team) - 25 Data
        // =========================================================================
        for ($i = 1; $i <= 25; $i++) {
            $targetColleague = $otherEmployees->isNotEmpty()
                ? $otherEmployees[$i % $otherEmployees->count()]
                : $employee;

            $reqDate = $today->copy()->subDays(35 - $i)->format('Y-m-d');
            $targetDate = Carbon::parse($reqDate)->addDays(2)->format('Y-m-d');
            $status = $swapStatuses[$i % count($swapStatuses)];

            SwapTeam::create([
                'company_id' => $company->id,
                'requestor_id' => $employee->id,
                'target_employee_id' => $targetColleague->id,
                'requestor_work_date' => $reqDate,
                'target_work_date' => $targetDate,
                'reason' => "Tukar shift tim dengan rekan {$targetColleague->name} untuk koordinasi task #$i",
                'status' => $status,
                'approved_by' => ($status === 'approved' || $status === 'rejected') ? $company->user_id : null,
            ]);
        }
        $this->command->info('✓ 25 Tukar Jadwal Tim dibuat.');

        // =========================================================================
        // 8. LAPORAN HARIAN (Daily Reports) - 25 Data
        // =========================================================================
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(26 - $i)->format('Y-m-d');
            DailyReport::create([
                'user_id' => $user->id,
                'date' => $date,
                'title' => "Laporan Kerja Harian #$i",
                'description' => "Menyelesaikan implementasi arsitektur pagination, refactoring view model, dan testing integrasi mobile hari ke-$i.",
            ]);
        }
        $this->command->info('✓ 25 Laporan Harian dibuat.');

        // =========================================================================
        // 9. PINJAMAN / KASBON (Loans) - 25 Data
        // =========================================================================
        $loanStatuses = ['approved', 'pending', 'rejected', 'paid'];
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(60 - ($i * 2))->format('Y-m-d');
            $status = $loanStatuses[$i % count($loanStatuses)];

            Loan::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => $date,
                'amount' => 500000 + ($i * 100000),
                'tenor' => ($i % 3) + 1,
                'description' => "Pengajuan pinjaman darurat operasional #$i",
                'status' => $status,
            ]);
        }
        $this->command->info('✓ 25 Pinjaman / Kasbon dibuat.');

        // =========================================================================
        // 10. REIMBURSEMENT (Klaim Biaya) - 25 Data
        // =========================================================================
        $reimbStatuses = ['pending', 'approved', 'paid', 'rejected'];
        for ($i = 1; $i <= 25; $i++) {
            $date = $today->copy()->subDays(30 - $i)->format('Y-m-d');
            $status = $reimbStatuses[$i % count($reimbStatuses)];

            Reimbursement::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => $date,
                'amount' => 75000 + ($i * 25000),
                'reason' => "Klaim biaya operasional meeting & transportasi lapangan #$i",
                'status' => $status,
                'payout_method' => 'direct',
                'approved_by' => in_array($status, ['approved', 'paid', 'rejected']) ? $company->user_id : null,
                'approved_at' => in_array($status, ['approved', 'paid']) ? Carbon::parse($date)->addDay() : null,
            ]);
        }
        $this->command->info('✓ 25 Reimbursement dibuat.');

        // =========================================================================
        // 11. PENGUMUMAN (Announcements) - 25 Data
        // =========================================================================
        for ($i = 1; $i <= 25; $i++) {
            $startDate = $today->copy()->subDays(30 - $i)->format('Y-m-d');
            $endDate = Carbon::parse($startDate)->addDays(14)->format('Y-m-d');

            Announcement::create([
                'company_id' => $company->id,
                'branch_id' => null, // Semua cabang
                'division_id' => null, // Semua divisi
                'title' => "Pengumuman Kantor #$i - Informasi Operasional Perusahaan",
                'content' => "Diberitahukan kepada seluruh karyawan mengenai update kebijakan dan jadwal kegiatan kantor edisi #$i.",
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
            ]);
        }
        $this->command->info('✓ 25 Pengumuman dibuat.');

        $this->command->info('=============================================');
        $this->command->info('SUKSES! Data dummy pagination untuk Abghi Fareihan berhasil di-generate.');
        $this->command->info('Silakan uji pagination (page 1, 2, 3) di Flutter.');
        $this->command->info('=============================================');
    }
}
