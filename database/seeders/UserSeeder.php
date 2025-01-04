<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([

            'name' => 'admin',
            'email' => 'admin1@admin.com',
            'password' => Hash::make('admin1234'),
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1,
        ]);
    }
}
