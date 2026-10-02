<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Reimbursement;
use Carbon\Carbon;

class ReimbursementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari perusahaan aktif (misal PT Goys Media atau perusahaan pertama)
        $company = Company::where('name_company', 'like', '%Goys%')->first() ?? Company::first();

        if (!$company) {
            $this->command->error('Tidak ada data perusahaan untuk seeding reimbursement.');
            return;
        }

        // Ambil minimal 2 karyawan dari perusahaan tersebut
        $employees = Employee::where('company_id', $company->id)->take(2)->get();

        if ($employees->isEmpty()) {
            $employees = Employee::take(2)->get();
        }

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada data karyawan untuk seeding reimbursement.');
            return;
        }

        $emp1 = $employees->get(0);
        $emp2 = $employees->get(1) ?? $emp1;

        // Permintaan Klaim 1: Pembelian Perlengkapan Kantor & ATK
        Reimbursement::create([
            'company_id' => $emp1->company_id,
            'employee_id' => $emp1->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'amount' => 350000,
            'reason' => 'Pembelian ATK & Tinta Printer untuk kebutuhan dokumen operasional kantor',
            'attachment' => 'reimbursements/3fbwXabNh2C0WhfE55i2PaS4oRTpVpENBzwgjaMo.png',
            'status' => 'pending',
            'payout_method' => null,
            'payroll_item_id' => null,
            'reject_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        // Permintaan Klaim 2: Biaya Transportasi & Bensin Dinas
        Reimbursement::create([
            'company_id' => $emp2->company_id,
            'employee_id' => $emp2->id,
            'date' => Carbon::now()->subDay()->format('Y-m-d'),
            'amount' => 175000,
            'reason' => 'Biaya transportasi & bensin kunjungan meeting operasional klien luar kantor',
            'attachment' => 'reimbursements/7nGdCvpWx0pBrAl0YdkjJm7AKzBAzmKEOTRaGMd9.png',
            'status' => 'pending',
            'payout_method' => null,
            'payroll_item_id' => null,
            'reject_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);


        $this->command->info("Berhasil membuat 2 data permintaan klaim biaya (pending) untuk karyawan {$emp1->name} dan {$emp2->name}.");
    }
}
