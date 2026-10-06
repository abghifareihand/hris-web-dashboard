<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\Carbon;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari perusahaan aktif (misal PT Goys Media atau perusahaan pertama)
        $company = Company::where('name_company', 'like', '%Goys%')->first() ?? Company::first();

        if (!$company) {
            $this->command->error('Tidak ada data perusahaan untuk seeding pinjaman/kasbon.');
            return;
        }

        // Ambil minimal beberapa karyawan dari perusahaan tersebut
        $employees = Employee::where('company_id', $company->id)->take(4)->get();

        if ($employees->isEmpty()) {
            $employees = Employee::take(4)->get();
        }

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada data karyawan untuk seeding pinjaman/kasbon.');
            return;
        }

        $emp1 = $employees->get(0);
        $emp2 = $employees->get(1) ?? $emp1;
        $emp3 = $employees->get(2) ?? $emp1;
        $emp4 = $employees->get(3) ?? $emp2;

        // 1. Pengajuan Pinjaman Pending 1
        Loan::create([
            'company_id' => $emp1->company_id,
            'employee_id' => $emp1->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'amount' => 3000000,
            'tenor' => 6,
            'description' => 'Biaya renovasi atap dan dapur rumah tinggal keluarga yang bocor.',
            'status' => 'pending',
        ]);

        // 2. Pengajuan Pinjaman Pending 2
        Loan::create([
            'company_id' => $emp2->company_id,
            'employee_id' => $emp2->id,
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'amount' => 1500000,
            'tenor' => 3,
            'description' => 'Pembayaran uang pangkal & SPP pendaftaran sekolah anak tahun ajaran baru.',
            'status' => 'pending',
        ]);

        // 3. Pengajuan Pinjaman Pending 3
        Loan::create([
            'company_id' => $emp3->company_id,
            'employee_id' => $emp3->id,
            'date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'amount' => 5000000,
            'tenor' => 10,
            'description' => 'Kebutuhan dana darurat pengobatan rawat inap orang tua di rumah sakit.',
            'status' => 'pending',
        ]);

        // 4. Pinjaman Approved (dengan cicilan) untuk riwayat
        $approvedLoan = Loan::create([
            'company_id' => $emp4->company_id,
            'employee_id' => $emp4->id,
            'date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'amount' => 2000000,
            'tenor' => 4,
            'description' => 'Pembelian perangkat laptop pendukung mobilitas kerja harian.',
            'status' => 'approved',
        ]);

        $monthlyAmount = $approvedLoan->amount / $approvedLoan->tenor;
        $startDate = Carbon::parse($approvedLoan->date)->startOfMonth();

        for ($i = 1; $i <= $approvedLoan->tenor; $i++) {
            $dueDate = $startDate->copy()->addMonths($i)->day(1);
            LoanInstallment::create([
                'loan_id' => $approvedLoan->id,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $monthlyAmount,
                'status' => $i === 1 ? 'paid' : 'unpaid',
            ]);
        }

        $this->command->info("Berhasil membuat 3 pengajuan pinjaman pending dan 1 pinjaman approved dengan jadwal cicilan.");
    }
}
