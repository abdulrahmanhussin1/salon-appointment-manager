<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Budget\Database\Seeders\RolesAndPermissionsSeeder as BudgetRolesAndPermissionsSeeder;

class RefreshRolesAndPermissions extends Command
{
    protected $signature = 'roles:refresh';
    protected $description = 'Refresh roles and permissions and reseed them';

    public function handle()
    {
        $this->info("Refreshing roles and permissions...");

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        DB::table(config('permission.table_names.permissions'))->truncate();
        DB::table(config('permission.table_names.role_has_permissions'))->truncate();
        DB::table(config('permission.table_names.roles'))->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->call(RolesAndPermissionsSeeder::class);

        $this->info('Roles and permissions refreshed successfully!');
    }

}
