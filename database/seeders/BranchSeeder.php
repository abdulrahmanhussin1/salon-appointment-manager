<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Branch::create([
            'name' => 'master branch',
            'address' => '123 Main St',
            'phone' => '123-456-7890',
            'email' => 'admin@master.com',
            'status' => 'active',
            'created_by' => 1,
        ]);

        Branch::factory()->count(5)->create();

    }
}
