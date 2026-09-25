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
        if (app()->environment(['local', 'testing']) && env('SEED_DEVELOPMENT_SIMULATION', true)) {
            $this->call(DevelopmentSimulationSeeder::class);
            return;
        }

        $this->call([
            UserSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminPanelSettingSeeder::class,
            BranchSeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
