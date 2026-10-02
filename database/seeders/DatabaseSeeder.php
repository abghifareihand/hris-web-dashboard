<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // 1. Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 2. PT ADR Project
        $ownerAdr = User::create([
            'name' => 'HR Manager ADR',
            'email' => 'hrd@adrproject.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);
        $companyAdr = Company::create([
            'user_id' => $ownerAdr->id,
            'name_company' => 'PT ADR Project',
            'email' => 'info@adrproject.com',
            'phone' => '081298765432',
            'business_type' => 'Technology',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan'
        ]);

        \App\Models\CompanyBpjsKesehatan::create([
            'company_id' => $companyAdr->id ?? 1, // Will use DB ID
            'employee_percent' => 1.00,
            'company_percent' => 4.00,
            'minimum_wage' => null,
            'maximum_wage' => 12000000,
        ]);

        \App\Models\CompanyBpjsKetenagakerjaan::create([
            'company_id' => $companyAdr->id ?? 1,
            'jht_active' => true,
            'jht_employee_percent' => 2.00,
            'jht_company_percent' => 3.70,
            'jp_active' => true,
            'jp_employee_percent' => 1.00,
            'jp_company_percent' => 2.00,
            'jp_maximum_wage' => 10042300,
            'jkk_active' => true,
            'jkk_percent' => 0.24,
            'jkm_active' => true,
            'jkm_percent' => 0.30,
        ]);

        // 3. PT Goys Media
        $ownerGoys = User::create([
            'name' => 'HR Manager Goys',
            'email' => 'hrd@goysmedia.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);
        $goysMedia = Company::create([
            'user_id' => $ownerGoys->id,
            'name_company' => 'PT Goys Media',
            'email' => 'info@goysmedia.com',
            'phone' => '081234567890',
            'business_type' => 'Technology',
            'province' => 'Jawa Barat',
            'city' => 'Bandung'
        ]);

        \App\Models\CompanyBpjsKesehatan::create([
            'company_id' => $goysMedia->id,
            'employee_percent' => 1.00,
            'company_percent' => 4.00,
            'minimum_wage' => null,
            'maximum_wage' => 12000000,
        ]);

        \App\Models\CompanyBpjsKetenagakerjaan::create([
            'company_id' => $goysMedia->id,
            'jht_active' => true,
            'jht_employee_percent' => 2.00,
            'jht_company_percent' => 3.70,
            'jp_active' => true,
            'jp_employee_percent' => 1.00,
            'jp_company_percent' => 2.00,
            'jp_maximum_wage' => 10042300,
            'jkk_active' => true,
            'jkk_percent' => 0.24,
            'jkm_active' => true,
            'jkm_percent' => 0.30,
        ]);

        // Setup PT Goys Media
        // Branches
        $branchBandung = Branch::create([
            'company_id' => $goysMedia->id,
            'name' => 'Cabang Bandung',
            'code' => 'CBG-BDO',
            'address' => 'Jl. Asia Afrika, Bandung'
        ]);
        $branchJakarta = Branch::create([
            'company_id' => $goysMedia->id,
            'name' => 'Cabang Jakarta',
            'code' => 'CBG-JKT',
            'address' => 'Jl. Sudirman, Jakarta'
        ]);
        $branchDepok = Branch::create([
            'company_id' => $goysMedia->id,
            'name' => 'Cabang Depok',
            'code' => 'CBG-DPK',
            'address' => 'Jl. Margonda Raya, Depok'
        ]);
        $branches = [$branchBandung, $branchJakarta, $branchDepok];

        // Divisions
        $divIt = Division::create(['company_id' => $goysMedia->id, 'name' => 'IT']);
        $divSales = Division::create(['company_id' => $goysMedia->id, 'name' => 'Sales']);
        $divisions = [$divIt, $divSales];

        // Positions
        $posManager = Position::create(['company_id' => $goysMedia->id, 'name' => 'Manager']);
        $posStaff = Position::create(['company_id' => $goysMedia->id, 'name' => 'Staff']);

        // 15 Shifts
        $shiftsList = [
            ['name' => 'Shift Reguler', 'clock_in' => '08:00:00', 'clock_out' => '17:00:00'],
            ['name' => 'Shift Reguler IT', 'clock_in' => '08:00:00', 'clock_out' => '17:00:00'],
            ['name' => 'Shift Pagi Operasional', 'clock_in' => '07:00:00', 'clock_out' => '15:30:00'],
            ['name' => 'Shift Siang Operasional', 'clock_in' => '15:00:00', 'clock_out' => '23:30:00'],
            ['name' => 'Shift Malam / NOC 24 Jam', 'clock_in' => '23:00:00', 'clock_out' => '07:30:00'],
            ['name' => 'Shift Customer Support Pagi', 'clock_in' => '06:00:00', 'clock_out' => '14:30:00'],
            ['name' => 'Shift Customer Support Sore', 'clock_in' => '14:00:00', 'clock_out' => '22:30:00'],
            ['name' => 'Shift Customer Support Malam', 'clock_in' => '22:00:00', 'clock_out' => '06:30:00'],
            ['name' => 'Shift Middle Fleksibel', 'clock_in' => '09:00:00', 'clock_out' => '18:00:00'],
            ['name' => 'Shift Eksekutif / Head Office', 'clock_in' => '08:30:00', 'clock_out' => '17:30:00'],
            ['name' => 'Shift Logistik & Gudang Pagi', 'clock_in' => '06:30:00', 'clock_out' => '15:00:00'],
            ['name' => 'Shift Logistik & Gudang Siang', 'clock_in' => '14:30:00', 'clock_out' => '23:00:00'],
            ['name' => 'Shift Weekend Standby', 'clock_in' => '08:00:00', 'clock_out' => '16:00:00'],
            ['name' => 'Shift Part Time Sesi Pagi', 'clock_in' => '08:00:00', 'clock_out' => '12:00:00'],
            ['name' => 'Shift Maintenance Server Subuh', 'clock_in' => '00:00:00', 'clock_out' => '06:00:00'],
        ];

        foreach ($shiftsList as $sData) {
            \App\Models\Shift::create([
                'company_id' => $goysMedia->id,
                'name' => $sData['name'],
                'clock_in' => $sData['clock_in'],
                'clock_out' => $sData['clock_out'],
            ]);
        }

        // Announcements
        \App\Models\Announcement::create([
            'company_id' => $goysMedia->id,
            'title' => 'Pembaruan Kebijakan Jam Kerja Fleksibel',
            'content' => 'Mulai tanggal 15 September 2026, seluruh staf dapat memanfaatkan waktu fleksibel kedatangan maksimal 15 menit dengan tetap memenuhi 8 jam kerja efektif harian.',
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-30',
            'is_active' => true,
        ]);
        \App\Models\Announcement::create([
            'company_id' => $goysMedia->id,
            'title' => 'Jadwal Town Hall Meeting Q3 2026',
            'content' => 'Town Hall Perusahaan kuartal ketiga akan diadakan secara hybrid pada tanggal 25 September 2026 pukul 14:00 WIB. Seluruh karyawan diharapkan hadir tepat waktu.',
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-26',
            'is_active' => true,
        ]);

        // Employees for each branch (2 per branch: 1 manager, 1 staff)
        foreach ($branches as $branch) {
            for ($i = 0; $i < 2; $i++) {
                $isManager = ($i === 0);
                $position = $isManager ? $posManager : $posStaff;
                $division = $faker->randomElement($divisions);
                
                $genderEnum = $faker->randomElement(['male', 'female']);
                $firstName = $faker->firstName($genderEnum);
                $lastName = $faker->lastName($genderEnum);
                $fullName = $firstName . ' ' . $lastName;
                
                $empUser = User::create([
                    'name' => $fullName,
                    'email' => strtolower($firstName . '.' . $lastName . $faker->unique()->randomNumber(2)) . '@goysmedia.com',
                    'password' => Hash::make('password123'),
                    'role' => 'employee',
                ]);

                $basicSalary = $isManager ? $faker->randomElement([12000000, 15000000]) : $faker->randomElement([5000000, 6500000, 7000000]);
                $bankName = $faker->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI']);

                $employee = Employee::create([
                    'user_id' => $empUser->id,
                    'company_id' => $goysMedia->id,
                    'name' => $fullName,
                    'email' => $empUser->email,
                    'phone' => '08' . $faker->numerify('##########'),
                    'gender' => $genderEnum,
                    'nik' => $faker->numerify('################'),
                    'nip' => $faker->numerify('EMP-####'),
                    'birth_date' => $faker->dateTimeBetween('-35 years', '-22 years')->format('Y-m-d'),
                    'address' => $faker->address,
                    'branch_id' => $branch->id,
                    'division_id' => $division->id,
                    'position_id' => $position->id,
                    'joined_at' => $faker->dateTimeBetween('-3 years', '-10 days')->format('Y-m-d'),
                    
                    // Bank Details
                    'bank_name' => $bankName,
                    'bank_account_number' => $faker->numerify('##########'),
                    'bank_account_name' => $fullName,

                    // Payroll Data
                    'basic_salary' => $basicSalary,
                    'fixed_allowance' => $isManager ? $faker->randomElement([0, 1500000, 2000000]) : $faker->randomElement([0, 300000, 500000]),
                    'daily_allowance' => 50000,
                    'other_allowance' => $faker->randomElement([0, 150000]),
                    'overtime_rate_per_hour' => 50000,
                    'late_penalty_type' => 'flat',
                    'late_penalty_nominal' => 50000,
                    'alpha_penalty_type' => 'flat',
                    'alpha_penalty_nominal' => 150000,
                    'npwp' => $faker->numerify('##.###.###.#-###.###'),
                    'ptkp_status' => $faker->randomElement(['TK/0', 'TK/1', 'K/0', 'K/1']),
                    'taxable' => true,
                    'bpjs_kesehatan_no' => $faker->numerify('000#########'),
                    'jht_no' => $faker->numerify('120#########'),
                    'jp_no' => $faker->numerify('130#########'),
                    'jkk_no' => $faker->numerify('140#########'),
                    'jkm_no' => $faker->numerify('150#########'),
                ]);

                // Create Mock Attendance for September 2026
                \App\Models\Attendance::create([
                    'company_id' => $goysMedia->id,
                    'employee_id' => $employee->id,
                    'date' => '2026-09-02',
                    'clock_in_time' => '2026-09-02 08:30:00',
                    'clock_out_time' => '2026-09-02 17:00:00',
                    'late_minutes' => 30,
                    'attendance_status' => 'present',
                ]);

                if ($i % 2 === 0) {
                    \App\Models\Attendance::create([
                        'company_id' => $goysMedia->id,
                        'employee_id' => $employee->id,
                        'date' => '2026-09-05',
                        'attendance_status' => 'alpha',
                        'late_minutes' => 0,
                    ]);
                }

                // Create Mock Approved Overtime for September 2026
                if ($i % 2 !== 0) {
                    \App\Models\Overtime::create([
                        'company_id' => $goysMedia->id,
                        'employee_id' => $employee->id,
                        'title' => 'Lembur Pekerjaan Akhir Bulan',
                        'date' => '2026-09-10',
                        'start_time' => '17:00:00',
                        'end_time' => '20:00:00',
                        'duration_minutes' => 180, // 3 hours
                        'description' => 'Project milestone release',
                        'status' => 'approved',
                        'approved_at' => '2026-09-10 20:30:00',
                    ]);
                }
            }
        }
        // Add 5 specific employees for PRORATA testing
        // Add employees for THR Testing & Payroll Mid-Month Prorata Testing
        $prorataConfigs = [
            // THR Testing Employees (Joined prior to Sept 2026)
            [
                'name' => 'Tester THR 1 Bulan (Ags 2026)',
                'email' => 'thr1bln@goysmedia.com',
                'nip' => 'EMP-THR-1',
                'joined_at' => '2026-08-14',
                'basic_salary' => 6000000,
                'fixed_allowance' => 1000000,
            ],
            [
                'name' => 'Tester THR 3 Bulan (Jun 2026)',
                'email' => 'thr3bln@goysmedia.com',
                'nip' => 'EMP-THR-3',
                'joined_at' => '2026-06-14',
                'basic_salary' => 6000000,
                'fixed_allowance' => 1000000,
            ],
            [
                'name' => 'Tester THR 6 Bulan (Mar 2026)',
                'email' => 'thr6bln@goysmedia.com',
                'nip' => 'EMP-THR-6',
                'joined_at' => '2026-03-14',
                'basic_salary' => 6000000,
                'fixed_allowance' => 1000000,
            ],

            // Payroll Mid-Month Joiners (Joined during September 2026 -> Will display warning & badge)
            [
                'name' => 'Budi Santoso (Masuk 05 Sep)',
                'email' => 'budi.prorata@goysmedia.com',
                'nip' => 'EMP-PRO-05',
                'joined_at' => '2026-09-05',
                'basic_salary' => 6000000,
                'fixed_allowance' => 500000,
            ],
            [
                'name' => 'Dewi Lestari (Masuk 10 Sep)',
                'email' => 'dewi.prorata@goysmedia.com',
                'nip' => 'EMP-PRO-10',
                'joined_at' => '2026-09-10',
                'basic_salary' => 6000000,
                'fixed_allowance' => 1000000,
            ],
            [
                'name' => 'Rian Pratama (Masuk 15 Sep)',
                'email' => 'rian.prorata@goysmedia.com',
                'nip' => 'EMP-PRO-15',
                'joined_at' => '2026-09-15',
                'basic_salary' => 5000000,
                'fixed_allowance' => 0,
            ],
            [
                'name' => 'Siti Nurhaliza (Masuk 20 Sep)',
                'email' => 'siti.prorata@goysmedia.com',
                'nip' => 'EMP-PRO-20',
                'joined_at' => '2026-09-20',
                'basic_salary' => 7500000,
                'fixed_allowance' => 500000,
            ],
            [
                'name' => 'Kevin Sanjaya (Masuk 25 Sep)',
                'email' => 'kevin.prorata@goysmedia.com',
                'nip' => 'EMP-PRO-25',
                'joined_at' => '2026-09-25',
                'basic_salary' => 5500000,
                'fixed_allowance' => 0,
            ],
        ];

        foreach ($prorataConfigs as $index => $pro) {
            $userPro = User::create([
                'name' => $pro['name'],
                'email' => $pro['email'],
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ]);

            Employee::create([
                'user_id' => $userPro->id,
                'company_id' => $goysMedia->id,
                'name' => $pro['name'],
                'email' => $pro['email'],
                'phone' => '0899999999' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'gender' => 'male',
                'nik' => '99999999999999' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'nip' => $pro['nip'],
                'birth_date' => '1995-01-15',
                'address' => 'Jl. Uji Coba Prorata No. ' . ($index + 1),
                'branch_id' => $branchJakarta->id,
                'division_id' => $divIt->id,
                'position_id' => $posStaff->id,
                'joined_at' => $pro['joined_at'],
                'bank_name' => 'BCA',
                'bank_account_number' => '12345678' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'bank_account_name' => $pro['name'],
                'basic_salary' => $pro['basic_salary'],
                'fixed_allowance' => $pro['fixed_allowance'],
                'daily_allowance' => 50000,
                'other_allowance' => 0,
                'overtime_rate_per_hour' => 50000,
                'late_penalty_type' => 'flat',
                'late_penalty_nominal' => 0,
                'alpha_penalty_type' => 'flat',
                'alpha_penalty_nominal' => 0,
                'npwp' => '00.000.000.0-000.0' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'ptkp_status' => 'TK/0',
                'taxable' => true,
                'bpjs_kesehatan_no' => '000123456' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'jht_no' => '120123456' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'jp_no' => '130123456' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'jkk_no' => '140123456' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'jkm_no' => '150123456' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Seed September 2026 Payroll for Goys Media in 'process' status
        $allEmployees = Employee::where('company_id', $goysMedia->id)->get();

        // Seed an approved Kasbon / Loan for testing
        $firstEmployee = $allEmployees->first();
        if ($firstEmployee) {
            $loan = \App\Models\Loan::create([
                'company_id' => $goysMedia->id,
                'employee_id' => $firstEmployee->id,
                'date' => '2026-08-25',
                'amount' => 1500000,
                'tenor' => 3,
                'description' => 'Pinjaman Kasbon Operasional Pribadi',
                'status' => 'approved',
            ]);

            \App\Models\LoanInstallment::create([
                'loan_id' => $loan->id,
                'due_date' => '2026-09-25',
                'amount' => 500000,
                'status' => 'unpaid',
            ]);
            \App\Models\LoanInstallment::create([
                'loan_id' => $loan->id,
                'due_date' => '2026-10-25',
                'amount' => 500000,
                'status' => 'unpaid',
            ]);
            \App\Models\LoanInstallment::create([
                'loan_id' => $loan->id,
                'due_date' => '2026-11-25',
                'amount' => 500000,
                'status' => 'unpaid',
            ]);
        }

        $bpjsTk = $goysMedia->bpjsKetenagakerjaan()->firstOrCreate(['company_id' => $goysMedia->id]);
        $bpjsKes = $goysMedia->bpjsKesehatan()->firstOrCreate(['company_id' => $goysMedia->id]);

        $payroll = \App\Models\Payroll::create([
            'company_id' => $goysMedia->id,
            'code' => 'PY-202609-PRORATA',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'branch_id' => null,
            'division_id' => null,
            'status' => 'process',
            'total_employees' => $allEmployees->count(),
            'total_amount' => 0,
        ]);

        $totalPayrollAmount = 0;
        foreach ($allEmployees as $emp) {
            $basisBpjsKes = $emp->basic_salary + $emp->fixed_allowance;
            $bpjsKesEmp = ($basisBpjsKes * 1.00) / 100;
            $bpjsKesComp = ($basisBpjsKes * 4.00) / 100;

            $basisBpjsTk = $emp->basic_salary + $emp->fixed_allowance;
            $basisJp = min($basisBpjsTk, 10042300);

            $jhtEmp = ($basisBpjsTk * 2.00) / 100;
            $jhtComp = ($basisBpjsTk * 3.70) / 100;
            $jpEmp = ($basisJp * 1.00) / 100;
            $jpComp = ($basisJp * 2.00) / 100;
            $jkkComp = ($basisBpjsTk * 0.24) / 100;
            $jkmComp = ($basisBpjsTk * 0.30) / 100;

            $dailyAllowance = 0;
            $totalEarnings = $emp->basic_salary + $emp->fixed_allowance + $dailyAllowance;
            $totalPenalty = 0;
            $otherDeductions = 0;
            $pph21Amount = 0;

            $totalDeductions = $totalPenalty + $otherDeductions + $pph21Amount + $bpjsKesEmp + $jhtEmp + $jpEmp;
            $totalBenefits = $bpjsKesComp + $jhtComp + $jpComp + $jkkComp + $jkmComp;
            $netSalary = $totalEarnings - $totalDeductions;
            $totalPayrollAmount += $netSalary;

            $payroll->items()->create([
                'employee_id' => $emp->id,
                'basic_salary' => $emp->basic_salary,
                'fixed_allowance' => $emp->fixed_allowance,
                'daily_allowance' => $dailyAllowance,
                'other_allowance' => 0,
                'overtime_amount' => 0,
                'bonus' => 0,
                'late_penalty_amount' => 0,
                'alpha_penalty_amount' => 0,
                'total_penalty' => 0,
                'other_deductions' => 0,
                'pph21_amount' => 0,
                'ptkp_status' => $emp->ptkp_status ?? 'TK/0',
                'bpjs_kesehatan_employee' => $bpjsKesEmp,
                'bpjs_kesehatan_company' => $bpjsKesComp,
                'jht_employee' => $jhtEmp,
                'jht_company' => $jhtComp,
                'jp_employee' => $jpEmp,
                'jp_company' => $jpComp,
                'jkk_company' => $jkkComp,
                'jkm_company' => $jkmComp,
                'total_earnings' => $totalEarnings,
                'total_benefits' => $totalBenefits,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'remarks' => null,
            ]);
        }

        $payroll->update(['total_amount' => $totalPayrollAmount]);

        $this->call(ReimbursementSeeder::class);
        $this->call(AttendanceRecapSeeder::class);
        $this->call(WorkScheduleSeeder::class);
        $this->call(AnnouncementSeeder::class);
        $this->call(AbghiFareihanSeeder::class);
        $this->call(PendingEmployeeSeeder::class);
        $this->call(ScheduleManagementSeeder::class);
    }
}
