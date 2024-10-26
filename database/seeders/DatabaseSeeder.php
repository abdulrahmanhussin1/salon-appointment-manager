<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Database\Seeders\ToolSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\EmployeeLevelSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ServiceCategorySeeder;
use Database\Seeders\AdminPanelSettingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        $this->call([
            UserSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminPanelSettingSeeder::class,
            ProductCategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            ToolSeeder::class,
            EmployeeLevelSeeder::class,
            ServiceCategorySeeder::class,
            EmployeeSeeder::class

        ]);
    }
}
