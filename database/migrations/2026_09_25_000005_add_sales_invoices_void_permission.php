<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            $guardName = config('auth.defaults.guard', 'web');

            $permission = Permission::firstOrCreate([
                'name' => 'sales_invoices.void',
                'guard_name' => $guardName,
            ], [
                'group' => 'sales_invoices',
                'created_at' => now(),
            ]);

            if (Schema::hasTable('roles')) {
                $roles = Role::whereIn('name', ['admin', 'cashier'])->get();
                foreach ($roles as $role) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'sales_invoices.void')->delete();
        }
    }
};
