<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\PendingEmployee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PendingEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name_company', 'like', '%Goys Media%')->first() 
            ?? Company::first();

        if (!$company) {
            return;
        }

        $branches = Branch::where('company_id', $company->id)->get();
        $divisions = Division::where('company_id', $company->id)->get();
        $positions = Position::where('company_id', $company->id)->get();

        $candidates = [
            [
                'name' => 'Dimas Arya Pratama',
                'email' => 'dimas.arya@gmail.com',
                'phone' => '081234567801',
                'address' => 'Jl. Dago Asri No. 12, Coblong, Bandung',
                'days_ago' => 1,
                'hours_ago' => 2,
            ],
            [
                'name' => 'Anisa Rahmawati',
                'email' => 'anisa.rahmawati@gmail.com',
                'phone' => '081234567802',
                'address' => 'Jl. R.E. Martadinata No. 45, Riau, Bandung',
                'days_ago' => 1,
                'hours_ago' => 5,
            ],
            [
                'name' => 'Fajar Nugraha',
                'email' => 'fajar.nugraha@gmail.com',
                'phone' => '081234567803',
                'address' => 'Jl. Tebet Barat Dalam No. 8, Jakarta Selatan',
                'days_ago' => 2,
                'hours_ago' => 1,
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza99@gmail.com',
                'phone' => '081234567804',
                'address' => 'Jl. Margonda Raya No. 102, Beji, Depok',
                'days_ago' => 2,
                'hours_ago' => 4,
            ],
            [
                'name' => 'Rizky Ramadhan',
                'email' => 'rizky.ramadhan@gmail.com',
                'phone' => '081234567805',
                'address' => 'Jl. Buah Batu No. 77, Bandung',
                'days_ago' => 3,
                'hours_ago' => 2,
            ],
            [
                'name' => 'Maya Anggraini',
                'email' => 'maya.anggraini@gmail.com',
                'phone' => '081234567806',
                'address' => 'Jl. Fatmawati Raya No. 15, Cilandak, Jakarta Selatan',
                'days_ago' => 3,
                'hours_ago' => 6,
            ],
            [
                'name' => 'Bagas Setiawan',
                'email' => 'bagas.setiawan@gmail.com',
                'phone' => '081234567807',
                'address' => 'Jl. Cinere Raya No. 24, Cinere, Depok',
                'days_ago' => 4,
                'hours_ago' => 3,
            ],
            [
                'name' => 'Putri Wulandari',
                'email' => 'putri.wulandari@gmail.com',
                'phone' => '081234567808',
                'address' => 'Jl. Setiabudi No. 110, Bandung',
                'days_ago' => 5,
                'hours_ago' => 1,
            ],
            [
                'name' => 'Hendra Wijaya',
                'email' => 'hendra.wijaya@gmail.com',
                'phone' => '081234567809',
                'address' => 'Jl. Kemang Raya No. 33, Mampang Prapatan, Jakarta Selatan',
                'days_ago' => 5,
                'hours_ago' => 7,
            ],
            [
                'name' => 'Rina Agustina',
                'email' => 'rina.agustina@gmail.com',
                'phone' => '081234567810',
                'address' => 'Jl. Akses UI No. 56, Kelapa Dua, Depok',
                'days_ago' => 6,
                'hours_ago' => 4,
            ],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@gmail.com',
                'phone' => '081234567811',
                'address' => 'Jl. Dipatiukur No. 89, Coblong, Bandung',
                'days_ago' => 7,
                'hours_ago' => 2,
            ],
            [
                'name' => 'Jessica Maharani',
                'email' => 'jessica.maharani@gmail.com',
                'phone' => '081234567812',
                'address' => 'Jl. Gatot Subroto No. 62, Jakarta Selatan',
                'days_ago' => 8,
                'hours_ago' => 5,
            ],
            [
                'name' => 'Bayu Wicaksono',
                'email' => 'bayu.wicaksono@gmail.com',
                'phone' => '081234567813',
                'address' => 'Jl. Sawangan Raya No. 40, Pancoran Mas, Depok',
                'days_ago' => 9,
                'hours_ago' => 3,
            ],
            [
                'name' => 'Nadia Safitri',
                'email' => 'nadia.safitri@gmail.com',
                'phone' => '081234567814',
                'address' => 'Jl. Cihampelas No. 120, Bandung',
                'days_ago' => 10,
                'hours_ago' => 1,
            ],
            [
                'name' => 'Gilang Permana',
                'email' => 'gilang.permana@gmail.com',
                'phone' => '081234567815',
                'address' => 'Jl. Pasar Minggu Raya No. 19, Jakarta Selatan',
                'days_ago' => 11,
                'hours_ago' => 4,
            ],
        ];

        foreach ($candidates as $i => $data) {
            $branch = $branches->isNotEmpty() ? $branches[$i % $branches->count()] : null;
            $division = $divisions->isNotEmpty() ? $divisions[$i % $divisions->count()] : null;
            $position = $positions->isNotEmpty() ? $positions[$i % $positions->count()] : null;

            $createdAt = Carbon::now()->subDays($data['days_ago'])->subHours($data['hours_ago']);

            PendingEmployee::create([
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'division_id' => $division?->id,
                'position_id' => $position?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make('password123'),
                'address' => $data['address'],
                'status' => 'pending',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
