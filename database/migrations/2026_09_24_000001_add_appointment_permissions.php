<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // $permissions = [
        //     'appointments.index',
        //     'appointments.show',
        //     'appointments.create',
        //     'appointments.edit',
        //     'appointments.destroy',
        // ];

        // $guardName = config('auth.defaults.guard', 'web');

        // foreach ($permissions as $name) {
        //     Permission::firstOrCreate([
        //         'name' => $name,
        //         'guard_name' => $guardName,
        //     ], [
        //         'group' => 'Appointments',
        //         'created_at' => now(),
        //     ]);
        // }

        // // Assign permissions to admin and cashier roles
        // $roles = Role::whereIn('name', ['admin', 'cashier'])->get();
        // foreach ($roles as $role) {
        //     $role->givePermissionTo($permissions);
        // }

        // // Grant permissions to users who have admin or cashier role
        // $users = User::all();
        // foreach ($users as $user) {
        //     if ($user->hasRole('admin') || $user->hasRole('cashier')) {
        //         $user->givePermissionTo($permissions);
        //     }
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // $permissions = [
        //     'appointments.index',
        //     'appointments.show',
        //     'appointments.create',
        //     'appointments.edit',
        //     'appointments.destroy',
        // ];

        // Permission::whereIn('name', $permissions)->delete();
    }
};
