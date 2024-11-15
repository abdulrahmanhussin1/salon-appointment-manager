<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gardName = config('auth.defaults.guard');
        $permissionsByRole = [
            'admin' => [
                /* Settings */
                'admin_panel_settings.index',
                'admin_panel_settings.update',

                /*roles*/
                'roles.index',
                'roles.show',
                'roles.create',
                'roles.edit',
                'roles.rolesPermissions',
                'roles.destroy',

                /*users*/
                'users.index',
                'users.show',
                'users.create',
                'users.edit',
                'users.destroy',

                /* units */
                'units.index',
                'units.show',
                'units.create',
                'units.edit',
                'units.destroy',

                /* suppliers */
                'suppliers.index',
                'suppliers.show',
                'suppliers.create',
                'suppliers.edit',
                'suppliers.destroy',

                /* product categories */
                'product_categories.index',
                'product_categories.show',
                'product_categories.create',
                'product_categories.edit',
                'product_categories.destroy',

                /* products */
                'products.index',
                'products.show',
                'products.create',
                'products.edit',
                'products.destroy',

                /* employee_levels */
                'employee_levels.index',
                'employee_levels.show',
                'employee_levels.create',
                'employee_levels.edit',
                'employee_levels.destroy',

                /* employees */
                'employees.index',
                'employees.show',
                'employees.create',
                'employees.edit',
                'employees.destroy',

                /* tools */
                'tools.index',
                'tools.show',
                'tools.create',
                'tools.edit',
                'tools.destroy',

                /* service_category */
                'service_categories.index',
                'service_categories.show',
                'service_categories.create',
                'service_categories.edit',
                'service_categories.destroy',

                /* services */
                'services.index',
                'services.show',
                'services.create',
                'services.edit',
                'services.destroy',

                /* customers */
                'customers.index',
                'customers.show',
                'customers.create',
                'customers.edit',
                'customers.destroy',


                /* branches */
                'branches.index',
                'branches.show',
                'branches.create',
                'branches.edit',
                'branches.destroy',

                /* purchase_invoices */
                'purchase_invoices.index',
                'purchase_invoices.show',
                'purchase_invoices.create',
                'purchase_invoices.edit',
                'purchase_invoices.destroy',


            ],
        ];

        $insertPermissions = fn($role) => collect($permissionsByRole[$role])
            ->map(fn($name) => DB::table(config('permission.table_names.permissions'))->insertGetId(['name' => $name, 'group' => ucfirst(explode('.', str_replace('_', ' ', $name))[0]), 'guard_name' => $gardName, 'created_at' => now(),]))
            ->toArray();

        $permissionIdsByRole = [
            'admin' => $insertPermissions('admin'),
        ];

        foreach ($permissionIdsByRole as $roleName => $permissionIds) {
            $role = Role::whereName($roleName)->first();
            if (!$role) {
                $role = Role::create([
                    'name' => $roleName,
                    'description' => 'Best for business owners and company administrators',
                    'guard_name' => $gardName,
                    'created_at' => now(),
                    'created_by' => 1
                ]);
            }
            DB::table(config('permission.table_names.role_has_permissions'))
                ->insert(
                    collect($permissionIds)->map(fn($id) => [
                        'role_id' => $role->id,
                        'permission_id' => $id,
                    ])->toArray()
                );
            $users = User::where('id', 1)->get();
            foreach ($users as $user) {
                $user->assignRole($role);
                $user->syncPermissions($role->permissions);
            }
        }
    }
}

