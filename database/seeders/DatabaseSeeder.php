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
