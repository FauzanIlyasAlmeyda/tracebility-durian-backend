<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [

            [
                'first_name' => 'Budi',
                'last_name' => 'Petani',
                'phone' => '081111111111',
                'email' => 'petani@duriantrace.com',
                'username' => 'petani',
                'password' => 'password',
                'role' => UserRole::Petani->value,
            ],

            [
                'first_name' => 'Andi',
                'last_name' => 'Pengepul',
                'phone' => '082222222222',
                'email' => 'pengepul@duriantrace.com',
                'username' => 'pengepul',
                'password' => 'password',
                'role' => UserRole::Pengepul->value,
            ],

            [
                'first_name' => 'Siti',
                'last_name' => 'Distributor',
                'phone' => '083333333333',
                'email' => 'distributor@duriantrace.com',
                'username' => 'distributor',
                'password' => 'password',
                'role' => UserRole::Distributor->value,
            ],

            [
                'first_name' => 'Rina',
                'last_name' => 'UMKM',
                'phone' => '084444444444',
                'email' => 'umkm@duriantrace.com',
                'username' => 'umkm',
                'password' => 'password',
                'role' => UserRole::Umkm->value,
            ],

            [
                'first_name' => 'Doni',
                'last_name' => 'Konsumen',
                'phone' => '085555555555',
                'email' => 'konsumen@duriantrace.com',
                'username' => 'konsumen',
                'password' => 'password',
                'role' => UserRole::Konsumen->value,
            ]

        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
