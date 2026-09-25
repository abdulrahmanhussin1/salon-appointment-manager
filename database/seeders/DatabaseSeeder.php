<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminPanelSettingSeeder::class,
            BranchSeeder::class,
            // ProductCategorySeeder::class,
            // UnitSeeder::class,
            PaymentMethodSeeder::class,
            //SupplierSeeder::class,
            //ProductSeeder::class,
            // ToolSeeder::class,
            // EmployeeLevelSeeder::class,
            //ServiceCategorySeeder::class,
            //EmployeeSeeder::class,

        ]);
    }
}
