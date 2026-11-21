<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Garage',
                'email' => 'admin@garage.com',
                'nik' => '3201012345670001',
                'phone' => '081234567801',
                'role' => 'admin',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'nik' => '3201012345670002',
                'phone' => '081234567802',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Siti Rahmawati',
                'email' => 'siti@example.com',
                'nik' => '3201012345670003',
                'phone' => '081234567803',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Ahmad Fadillah',
                'email' => 'ahmad@example.com',
                'nik' => '3201012345670004',
                'phone' => '081234567804',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@example.com',
                'nik' => '3201012345670005',
                'phone' => '081234567805',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Rizky Pratama',
                'email' => 'rizky@example.com',
                'nik' => '3201012345670006',
                'phone' => '081234567806',
                'role' => 'user',
                'is_verified' => false,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Citra Handayani',
                'email' => 'citra@example.com',
                'nik' => '3201012345670007',
                'phone' => '081234567807',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Fajar Nugraha',
                'email' => 'fajar@example.com',
                'nik' => '3201012345670008',
                'phone' => '081234567808',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Gita Permata',
                'email' => 'gita@example.com',
                'nik' => '3201012345670009',
                'phone' => '081234567809',
                'role' => 'user',
                'is_verified' => false,
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Hendra Kusuma',
                'email' => 'hendra@example.com',
                'nik' => '3201012345670010',
                'phone' => '081234567810',
                'role' => 'user',
                'is_verified' => true,
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
