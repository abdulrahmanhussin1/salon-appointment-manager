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
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1,
        ]);
    }
}
