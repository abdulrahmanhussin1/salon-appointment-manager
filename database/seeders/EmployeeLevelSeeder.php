<?php

namespace Database\Seeders;

use App\Models\EmployeeLevel;
use Illuminate\Database\Seeder;

class EmployeeLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeLevel::factory()->count(5)->create();
    }
}
