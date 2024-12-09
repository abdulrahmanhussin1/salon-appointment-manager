<?php

namespace Database\Seeders;

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
        $branch = Branch::create([
            'name' => 'master branch',
            'address' => '123 Main St',
            'phone' => '123-456-7890',
            'email' => 'admin@master.com',
            'status' => 'active',
            'created_by' => 1,
        ]);


        Inventory::create([
            'name'=>'master Inventory',
            'branch_id' => $branch->id,
            'created_by' => 1,
        ]);
    }
}
