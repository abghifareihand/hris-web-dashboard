<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            // Employee 1 with User Account
            $user1 = User::create([
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@hrispro.com',
                'password' => Hash::make('password123'),
            ]);

            Employee::create([
                'user_id' => $user1->id,
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'email' => 'budi.santoso@hrispro.com',
                'phone' => '081234567890',
                'gender' => 'male',
                'date_of_birth' => '1990-05-15',
                'marital_status' => 'married',
                'address' => 'Jl. Merdeka No. 123',
                'city' => 'Jakarta',
            ]);

            // Employee 2 with User Account
            $user2 = User::create([
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@hrispro.com',
                'password' => Hash::make('password123'),
            ]);

            Employee::create([
                'user_id' => $user2->id,
                'first_name' => 'Siti',
                'last_name' => 'Aminah',
                'email' => 'siti.aminah@hrispro.com',
                'phone' => '089876543210',
                'gender' => 'female',
                'date_of_birth' => '1992-08-20',
                'marital_status' => 'single',
                'address' => 'Jl. Sudirman No. 45',
                'city' => 'Bandung',
            ]);

            // Employee 3 without User Account
            Employee::create([
                'user_id' => null,
                'first_name' => 'Agus',
                'last_name' => 'Setiawan',
                'email' => 'agus.setiawan@example.com',
                'phone' => '087654321098',
                'gender' => 'male',
                'date_of_birth' => '1988-11-10',
                'marital_status' => 'married',
                'address' => 'Jl. Gatot Subroto No. 10',
                'city' => 'Surabaya',
            ]);

            // Employee 4 without User Account
            Employee::create([
                'user_id' => null,
                'first_name' => 'Ratna',
                'last_name' => 'Sari',
                'email' => 'ratna.sari@example.com',
                'phone' => '085432109876',
                'gender' => 'female',
                'date_of_birth' => '1995-03-25',
                'marital_status' => 'single',
                'address' => 'Jl. Diponegoro No. 88',
                'city' => 'Yogyakarta',
            ]);

            // Add 20 random employees via factory
            Employee::factory(20)->create();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding employees: ' . $e->getMessage());
        }
    }
}
