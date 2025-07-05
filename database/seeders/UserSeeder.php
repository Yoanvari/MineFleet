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
        // Admin pool kendaraan
        foreach (range(1, 3) as $i) {
            User::create([
                'name'              => "Admin {$i}",
                'email'             => "admin{$i}@minefleet.com",
                'password'          => Hash::make('password123'),
                'role'              => 'admin',
            ]);
        }

        // 3 approver (atasan level 1 & 2)
        foreach (range(1, 3) as $i) {
            User::create([
                'name'              => "Approver {$i}",
                'email'             => "approver{$i}@minefleet.com",
                'password'          => Hash::make('password123'),
                'role'              => 'approver',
            ]);
        }
    }
}
