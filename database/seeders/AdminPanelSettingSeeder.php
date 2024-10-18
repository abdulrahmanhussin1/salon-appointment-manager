<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminPanelSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_panel_settings')->insert([
            'system_name' => ' Salon ',
            'system_phone' => '012345678910',
            'system_address' => 'Cairo Egypt',
            'system_notes' => 'system Notes from the system settings page are available in the system settings page in the system settings page in the system settings page in the system settings page in the ',
            'system_logo'=> asset('admin-assets/assets/img/logo.jpg'),
            //'status' => 'active', // Enum value
            'created_by' => 1, // Assuming the user with ID 1 exists
            'updated_by' => 1, // Assuming the user with ID 1 exists
            'deleted_by' => null, // No deletion record yet
            'created_at' => Carbon::now(),
            'deleted_at' => null,
        ]);
    }
}
