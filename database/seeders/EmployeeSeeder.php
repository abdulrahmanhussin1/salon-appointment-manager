<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeWage;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::factory()->count(100)->create();
        EmployeeWage::factory()->count(100)->create();
    }
}
