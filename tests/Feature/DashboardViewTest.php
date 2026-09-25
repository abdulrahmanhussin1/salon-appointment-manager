<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierUser;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->cashierUser = User::create([
            'name' => 'Cashier Amy',
            'email' => 'cashier_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        Permission::firstOrCreate(['name' => 'reports.index', 'guard_name' => 'web'], ['group' => 'reports']);
        Permission::firstOrCreate(['name' => 'employees.index', 'guard_name' => 'web'], ['group' => 'employees']);
        Permission::firstOrCreate(['name' => 'inventories.index', 'guard_name' => 'web'], ['group' => 'inventories']);
        Permission::firstOrCreate(['name' => 'expenses.index', 'guard_name' => 'web'], ['group' => 'expenses']);
        Permission::firstOrCreate(['name' => 'appointments.index', 'guard_name' => 'web'], ['group' => 'appointments']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $adminRole->givePermissionTo(['reports.index', 'employees.index', 'inventories.index', 'expenses.index', 'appointments.index']);

        $this->adminUser->assignRole('admin');
        $this->cashierUser->assignRole('cashier');

        $this->branch = Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Cashier Level',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $emp = Employee::create([
            'name' => 'Amy Cashier',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashierUser->employee_id = $emp->id;
        $this->cashierUser->save();
    }

    public function test_guest_is_redirected_from_admin_home(): void
    {
        $response = $this->get(route('home.index'));
        $response->assertRedirect();
    }

    public function test_admin_can_view_full_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('home.index'));

        $response->assertOk()
            ->assertSee('dashboard-container')
            ->assertSee('dashboard-header-card')
            ->assertSee('dashboard-filters-bar')
            ->assertSee('kpi-grid')
            ->assertSee('operational-section')
            ->assertSee('revenue-trend-card')
            ->assertSee('branch-expense-section')
            ->assertSee('staff-performance-card')
            ->assertSee('alerts-inventory-section')
            ->assertSee('recent-activity-card');
    }

    public function test_cashier_can_view_dashboard_with_role_scoped_elements(): void
    {
        $response = $this->actingAs($this->cashierUser)->get(route('home.index'));

        $response->assertOk()
            ->assertSee('dashboard-container')
            ->assertSee('kpi-grid')
            ->assertSee('operational-section')
            ->assertDontSee('revenue-trend-card')
            ->assertDontSee('branch-expense-section');
    }
}
