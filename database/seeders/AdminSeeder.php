<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'username' => 'admin',
            'password' => 'admin123',
            'name' => 'Administrator',
            'email' => 'admin@cemetery.com',
        ]);

        Admin::create([
            'username' => 'manager',
            'password' => 'manager123',
            'name' => 'Cemetery Manager',
            'email' => 'manager@cemetery.com',
        ]);
    }
}
