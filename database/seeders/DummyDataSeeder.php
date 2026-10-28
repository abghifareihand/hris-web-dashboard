<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyBpjsKesehatan;
use App\Models\CompanyBpjsKetenagakerjaan;
use App\Models\CompanyPayrollSetting;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveCategory;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $currentYear = (int) date('Y');
        $defaultPassword = Hash::make('password123');

        // =========================================================================
        // 1. OWNER 1 - PT GOYS MEDIA
        // =========================================================================
        $ownerGoys = User::create([
            'name' => 'HRD GOYS',
            'email' => 'hrd@goysmedia.com',
            'phone' => '081298765432',
            'gender' => 'male',
            'birth_date' => '1990-05-15',
            'address' => 'Jl. TB Simatupang No. 18, Cilandak, Jakarta Selatan',
            'password' => $defaultPassword,
            'role' => 'owner',
        ]);

        $companyGoys = Company::create([
            'user_id' => $ownerGoys->id,
            'name_company' => 'PT Goys Media',
            'email' => 'info@goysmedia.com',
            'phone' => '081298765432',
            'address' => 'Jl. TB Simatupang No. 18, Cilandak',
            'business_type' => 'Technology',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
        ]);

        // =========================================================================
        // 2. OWNER 2 - PT ADR PROJECT
        // =========================================================================
        $ownerAdr = User::create([
            'name' => 'HRD ADR',
            'email' => 'hrd@adrproject.com',
            'phone' => '081298765433',
            'gender' => 'female',
            'birth_date' => '1992-08-20',
            'address' => 'Jl. Ir. H. Juanda No. 88, Dago, Bandung',
            'password' => $defaultPassword,
            'role' => 'owner',
        ]);

        $companyAdr = Company::create([
            'user_id' => $ownerAdr->id,
            'name_company' => 'PT ADR Project',
            'email' => 'info@adrproject.com',
            'phone' => '081298765432',
            'address' => 'Jl. Ir. H. Juanda No. 88, Dago',
            'business_type' => 'Technology',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
        ]);

        // =========================================================================
        // 3. SETUP PERUSAHAAN (SETTINGS, CABANG, DIVISI, JABATAN, KATEGORI CUTI, SHIFT)
        // =========================================================================
        $companies = [
            [
                'model' => $companyGoys,
                'branches' => ['Kantor Pusat Jakarta', 'Cabang Bandung'],
                'domain' => 'goysmedia.com',
                'prefix_nip' => 'EMP-GM',
                'main_employee' => [
                    'name' => 'Abghi Fareihan',
                    'email' => 'abghi@goysmedia.com',
                    'gender' => 'male',
                    'phone' => '081234567001',
                    'basic_salary' => 15000000,
                ],
                'other_employees' => [
                    ['name' => 'Budi Santoso', 'gender' => 'male', 'salary' => 8500000],
                    ['name' => 'Citra Lestari', 'gender' => 'female', 'salary' => 7500000],
                    ['name' => 'Dimas Anggara', 'gender' => 'male', 'salary' => 9000000],
                    ['name' => 'Eka Prasetya', 'gender' => 'male', 'salary' => 8000000],
                    ['name' => 'Fajar Nugraha', 'gender' => 'male', 'salary' => 7000000],
                    ['name' => 'Gita Permata', 'gender' => 'female', 'salary' => 7500000],
                    ['name' => 'Hendra Gunawan', 'gender' => 'male', 'salary' => 11000000],
                    ['name' => 'Intan Maharani', 'gender' => 'female', 'salary' => 8000000],
                    ['name' => 'Joko Widodo', 'gender' => 'male', 'salary' => 9500000],
                    ['name' => 'Kartika Dewi', 'gender' => 'female', 'salary' => 7200000],
                    ['name' => 'Lukman Hakim', 'gender' => 'male', 'salary' => 8200000],
                    ['name' => 'Maya Anggraini', 'gender' => 'female', 'salary' => 7800000],
                    ['name' => 'Nanda Pratama', 'gender' => 'male', 'salary' => 8800000],
                    ['name' => 'Olivia Rahma', 'gender' => 'female', 'salary' => 7400000],
                    ['name' => 'Pandu Wicaksono', 'gender' => 'male', 'salary' => 10500000],
                    ['name' => 'Qori Amalia', 'gender' => 'female', 'salary' => 7600000],
                    ['name' => 'Rizky Ramadhan', 'gender' => 'male', 'salary' => 8900000],
                    ['name' => 'Sari Indah', 'gender' => 'female', 'salary' => 7100000],
                    ['name' => 'Taufik Hidayat', 'gender' => 'male', 'salary' => 9200000],
                    ['name' => 'Utami Putri', 'gender' => 'female', 'salary' => 7300000],
                    ['name' => 'Vicky Firmansyah', 'gender' => 'male', 'salary' => 8400000],
                    ['name' => 'Wahyu Hidayat', 'gender' => 'male', 'salary' => 8700000],
                    ['name' => 'Yuda Pratama', 'gender' => 'male', 'salary' => 9100000],
                    ['name' => 'Zahra Aulia', 'gender' => 'female', 'salary' => 7500000],
                ],
            ],
            [
                'model' => $companyAdr,
                'branches' => ['Kantor Pusat Bandung', 'Cabang Jakarta'],
                'domain' => 'adrproject.com',
                'prefix_nip' => 'EMP-ADR',
                'main_employee' => [
                    'name' => 'Agus Wicaksono',
                    'email' => 'agus@adrproject.com',
                    'gender' => 'male',
                    'phone' => '081234568001',
                    'basic_salary' => 14000000,
                ],
                'other_employees' => [
                    ['name' => 'Aditya Pratama', 'gender' => 'male', 'salary' => 8500000],
                    ['name' => 'Bella Safitri', 'gender' => 'female', 'salary' => 7200000],
                    ['name' => 'Candra Wijaya', 'gender' => 'male', 'salary' => 9000000],
                    ['name' => 'Dwi Cahyono', 'gender' => 'male', 'salary' => 7800000],
                    ['name' => 'Erna Wulandari', 'gender' => 'female', 'salary' => 7500000],
                    ['name' => 'Farhan Maulana', 'gender' => 'male', 'salary' => 8200000],
                    ['name' => 'Gilang Ramadhan', 'gender' => 'male', 'salary' => 8900000],
                    ['name' => 'Hesti Yuliana', 'gender' => 'female', 'salary' => 7400000],
                    ['name' => 'Irfan Setiawan', 'gender' => 'male', 'salary' => 9500000],
                    ['name' => 'Julia Rahmawati', 'gender' => 'female', 'salary' => 7600000],
                    ['name' => 'Kevin Sanjaya', 'gender' => 'male', 'salary' => 11000000],
                    ['name' => 'Larasati Kusuma', 'gender' => 'female', 'salary' => 7900000],
                    ['name' => 'Muhammad Fikri', 'gender' => 'male', 'salary' => 8700000],
                    ['name' => 'Nadia Novita', 'gender' => 'female', 'salary' => 7300000],
                    ['name' => 'Oki Kurniawan', 'gender' => 'male', 'salary' => 8400000],
                    ['name' => 'Putri Anggita', 'gender' => 'female', 'salary' => 7100000],
                    ['name' => 'Rendy Syahputra', 'gender' => 'male', 'salary' => 9100000],
                    ['name' => 'Siska Amelia', 'gender' => 'female', 'salary' => 7700000],
                    ['name' => 'Tri Wahyuni', 'gender' => 'female', 'salary' => 8300000],
                    ['name' => 'Umar Faruq', 'gender' => 'male', 'salary' => 8600000],
                    ['name' => 'Vina Panduwinata', 'gender' => 'female', 'salary' => 7500000],
                    ['name' => 'Wawan Setiawan', 'gender' => 'male', 'salary' => 8800000],
                    ['name' => 'Yogi Pratama', 'gender' => 'male', 'salary' => 9200000],
                    ['name' => 'Zulkifli Hasan', 'gender' => 'male', 'salary' => 9800000],
                ],
            ],
        ];

        foreach ($companies as $compData) {
            $company = $compData['model'];

            // Settings Perusahaan
            CompanyBpjsKesehatan::create(['company_id' => $company->id]);
            CompanyBpjsKetenagakerjaan::create(['company_id' => $company->id]);
            CompanyPayrollSetting::create(['company_id' => $company->id]);

            // Cabang (Branches)
            $branchIds = [];
            foreach ($compData['branches'] as $bName) {
                $b = Branch::create([
                    'company_id' => $company->id,
                    'name' => $bName,
                    'address' => $company->city . ', Indonesia',
                    'latitude' => -6.9175,
                    'longitude' => 107.6191,
                    'radius' => 100,
                ]);
                $branchIds[] = $b->id;
            }

            // Divisi (Divisions)
            $divisionNames = ['IT & Engineering', 'Human Resources', 'Finance & Accounting', 'Marketing & Sales', 'Operations'];
            $divisionIds = [];
            foreach ($divisionNames as $dName) {
                $d = Division::create(['company_id' => $company->id, 'name' => $dName]);
                $divisionIds[] = $d->id;
            }

            // Jabatan (Positions)
            $positionNames = ['Manager', 'Team Lead', 'Senior Staff', 'Staff', 'Junior Staff'];
            $positionIds = [];
            foreach ($positionNames as $pName) {
                $p = Position::create(['company_id' => $company->id, 'name' => $pName]);
                $positionIds[] = $p->id;
            }

            // Kategori Cuti (Leave Categories)
            $leaveCatData = [
                ['name' => 'Cuti Tahunan', 'quota' => 12],
                ['name' => 'Cuti Bersama', 'quota' => 5],
                ['name' => 'Cuti Khusus / Menikah', 'quota' => 3],
                ['name' => 'Izin Sakit', 'quota' => 10],
            ];
            $categories = [];
            foreach ($leaveCatData as $lcd) {
                $categories[] = LeaveCategory::create([
                    'company_id' => $company->id,
                    'name' => $lcd['name'],
                    'default_quota' => $lcd['quota'],
                ]);
            }

            // Shift Kerja
            Shift::create([
                'company_id' => $company->id,
                'name' => 'Shift Reguler',
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
            ]);
            Shift::create([
                'company_id' => $company->id,
                'name' => 'Shift Siang',
                'clock_in' => '13:00:00',
                'clock_out' => '21:00:00',
            ]);

            // EMPLOYEE #1 (UTAMA)
            $mainEmpData = $compData['main_employee'];
            $mainUser = User::create([
                'name' => $mainEmpData['name'],
                'email' => $mainEmpData['email'],
                'phone' => $mainEmpData['phone'],
                'gender' => $mainEmpData['gender'],
                'birth_date' => '1996-05-15',
                'address' => $company->city,
                'password' => $defaultPassword,
                'role' => 'employee',
            ]);

            $mainEmp = Employee::create([
                'user_id' => $mainUser->id,
                'company_id' => $company->id,
                'name' => $mainEmpData['name'],
                'email' => $mainEmpData['email'],
                'nik' => '3271' . rand(100000000000, 999999999999),
                'nip' => $compData['prefix_nip'] . '-001',
                'branch_id' => $branchIds[0],
                'division_id' => $divisionIds[0],
                'position_id' => $positionIds[1],
                'joined_at' => '2024-01-01',
                'bank_name' => 'BCA',
                'bank_account_number' => '8271' . rand(100000, 999999),
                'bank_account_name' => $mainEmpData['name'],
                'payroll_cycle' => 'monthly',
                'basic_salary' => $mainEmpData['basic_salary'],
                'fixed_allowance' => 1500000,
                'daily_allowance' => 50000,
                'other_allowance' => 500000,
                'overtime_rate_per_hour' => 50000,
                'npwp' => '09.123.456.7-012.000',
                'ptkp_status' => 'TK/0',
                'taxable' => true,
                'bpjs_kesehatan_no' => '0001234567890',
                'jht_no' => '21012345678',
                'jp_no' => '21012345678',
                'jkk_no' => '21012345678',
                'jkm_no' => '21012345678',
                'is_active' => true,
            ]);

            // Kuota cuti Employee #1
            foreach ($categories as $cat) {
                LeaveBalance::create([
                    'company_id' => $company->id,
                    'employee_id' => $mainEmp->id,
                    'leave_category_id' => $cat->id,
                    'year' => $currentYear,
                    'quota' => $cat->default_quota,
                    'used' => 0,
                ]);
            }

            // 24 EMPLOYEES LAINNYA
            $counter = 2;
            foreach ($compData['other_employees'] as $other) {
                $emailSlug = strtolower(str_replace(' ', '.', $other['name']));
                $email = $emailSlug . '@' . $compData['domain'];

                $u = User::create([
                    'name' => $other['name'],
                    'email' => $email,
                    'phone' => '0812' . rand(10000000, 99999999),
                    'gender' => $other['gender'],
                    'birth_date' => '199' . rand(0, 9) . '-' . sprintf('%02d', rand(1, 12)) . '-' . sprintf('%02d', rand(1, 28)),
                    'address' => $company->city,
                    'password' => $defaultPassword,
                    'role' => 'employee',
                ]);

                $nip = sprintf('%s-%03d', $compData['prefix_nip'], $counter);
                $branchId = $branchIds[$counter % count($branchIds)];
                $divisionId = $divisionIds[$counter % count($divisionIds)];
                $positionId = $positionIds[$counter % count($positionIds)];

                // 20 Karyawan pertama ada BPJS (termasuk Employee #1), sisanya dikosongkan
                $hasBpjs = $counter <= 20;
                $bpjsKesehatanNo = $hasBpjs ? ('000' . rand(1000000000, 9999999999)) : null;
                $bpjsTkNo = $hasBpjs ? ('210' . rand(10000000, 99999999)) : null;

                $emp = Employee::create([
                    'user_id' => $u->id,
                    'company_id' => $company->id,
                    'name' => $other['name'],
                    'email' => $email,
                    'nik' => '3271' . rand(100000000000, 999999999999),
                    'nip' => $nip,
                    'branch_id' => $branchId,
                    'division_id' => $divisionId,
                    'position_id' => $positionId,
                    'joined_at' => '2024-' . sprintf('%02d', rand(1, 12)) . '-01',
                    'bank_name' => ($counter % 2 === 0) ? 'BCA' : 'Mandiri',
                    'bank_account_number' => '5210' . rand(100000, 999999),
                    'bank_account_name' => $other['name'],
                    'payroll_cycle' => 'monthly',
                    'basic_salary' => $other['salary'],
                    'fixed_allowance' => 1000000,
                    'daily_allowance' => 40000,
                    'other_allowance' => 250000,
                    'overtime_rate_per_hour' => 40000,
                    'npwp' => $hasBpjs ? ('09.' . rand(100, 999) . '.' . rand(100, 999) . '.' . rand(1, 9) . '-012.000') : null,
                    'ptkp_status' => ($counter % 3 === 0) ? 'K/1' : (($counter % 2 === 0) ? 'K/0' : 'TK/0'),
                    'taxable' => true,
                    'bpjs_kesehatan_no' => $bpjsKesehatanNo,
                    'jht_no' => $bpjsTkNo,
                    'jp_no' => $bpjsTkNo,
                    'jkk_no' => $bpjsTkNo,
                    'jkm_no' => $bpjsTkNo,
                    'is_active' => true,
                ]);

                // Kuota cuti karyawan
                foreach ($categories as $cat) {
                    LeaveBalance::create([
                        'company_id' => $company->id,
                        'employee_id' => $emp->id,
                        'leave_category_id' => $cat->id,
                        'year' => $currentYear,
                        'quota' => $cat->default_quota,
                        'used' => 0,
                    ]);
                }

                $counter++;
            }
        }
    }
}
