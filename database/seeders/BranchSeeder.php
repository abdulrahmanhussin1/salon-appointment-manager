<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the master branch
        $branch = Branch::create([
            'name' => 'master branch',
            'address' => '123 Main St',
            'phone' => '123-456-7890',
            'email' => 'admin@master.com',
            'status' => 'active',
            'created_by' => 1,
        ]);

        // Create the master branch's inventory (single inventory)
        Inventory::create([
            'name' => 'master Inventory',
            'branch_id' => $branch->id,
            'created_by' => 1,
        ]);

        $user = User::find(1);
        $user->update([
            'employee_id' => $branch->id, // Assign employee_id equal to the user ID
        ]);


        // Create 10 additional branches and assign them 10 inventories each
        // Branch::factory(10)->create()->each(function ($branch) {
        //     // Create 10 inventories for each branch
        //     foreach (range(1, 10) as $index) {
        //         Inventory::create([
        //             'name' => $branch->name . ' Inventory ' . $index,
        //             'branch_id' => $branch->id,
        //             'created_by' => 1,
        //         ]);
        //     }
        // });
    }

}
