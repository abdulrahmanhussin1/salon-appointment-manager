<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
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
        Employee::factory()->count(5)->create();
        //EmployeeWage::factory()->count(5)->create();

    $branches = Branch::all();

    foreach ($branches as $branch) {
        $branch['manager_id'] = rand(1, 3); // Assuming you have 10 employees
        $branch->save();
    }

        $users = User::whereIn('id', [1, 2])->get();

        foreach ($users as $user) {
            $user->update([
                'employee_id' => $user->id, // Assign employee_id equal to the user ID
            ]);
        }



    }
}
