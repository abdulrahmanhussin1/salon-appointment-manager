<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\EmployeeWage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeWageCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Branch $branch;

    protected EmployeeLevel $employeeLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'employees.index',
            'employees.show',
            'employees.create',
            'employees.edit',
            'employees.destroy',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web', 'group' => 'employees']);
        }

        \Illuminate\Support\Facades\View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->adminUser->givePermissionTo($permissions);

        $this->branch = Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->employeeLevel = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_creating_employee_via_web_creates_exactly_one_wage_record_with_form_data(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'national_id' => 'NAT123456',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'salary_type' => 'monthly',
            'basic_salary' => 5000,
            'bonus_salary' => 500,
            'allowance1' => 100,
            'allowance2' => 50,
            'allowance3' => 50,
            'total_salary' => 5700,
            'working_hours' => 8,
            'overtime_rate' => 25,
            'penalty_late_hour' => 15,
            'penalty_absence_day' => 100,
            'sales_target_settings' => 'no',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $payload);

        $response->assertSessionHasNoErrors();

        $employee = Employee::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($employee);

        // Assert exactly ONE wage record exists for this employee
        $wageCount = EmployeeWage::where('employee_id', $employee->id)->count();
        $this->assertEquals(1, $wageCount, 'There should be exactly one EmployeeWage record created.');

        // Verify the wage record contains all form data (not empty defaults from boot)
        $wage = $employee->wage;
        $this->assertNotNull($wage);
        $this->assertEquals('monthly', $wage->salary_type);
        $this->assertEquals(5000, (float) $wage->basic_salary);
        $this->assertEquals(500, (float) $wage->bonus_salary);
        $this->assertEquals(5700, (float) $wage->total_salary);
        $this->assertEquals(8, (float) $wage->working_hours);
        $this->assertEquals(25, (float) $wage->overtime_rate);
    }

    public function test_updating_employee_updates_existing_wage_and_does_not_create_duplicates(): void
    {
        $employee = Employee::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '9876543210',
            'national_id' => 'NAT987654',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $wage = EmployeeWage::create([
            'employee_id' => $employee->id,
            'salary_type' => 'monthly',
            'basic_salary' => 4000,
            'bonus_salary' => 200,
            'allowance1' => 0,
            'allowance2' => 0,
            'allowance3' => 0,
            'total_salary' => 4200,
            'working_hours' => 8,
        ]);

        $updatePayload = [
            'name' => 'Jane Smith Updated',
            'email' => 'jane.smith@example.com',
            'phone' => '9876543210',
            'national_id' => 'NAT987654',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'salary_type' => 'monthly',
            'basic_salary' => 6000,
            'bonus_salary' => 400,
            'allowance1' => 100,
            'allowance2' => 0,
            'allowance3' => 0,
            'total_salary' => 6500,
            'working_hours' => 9,
            'overtime_rate' => 30,
            'penalty_late_hour' => 20,
            'penalty_absence_day' => 120,
            'sales_target_settings' => 'no',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('employees.update', $employee->id), $updatePayload);

        $response->assertSessionHasNoErrors();

        // Check wage count is still strictly 1
        $wageCount = EmployeeWage::where('employee_id', $employee->id)->count();
        $this->assertEquals(1, $wageCount);

        // Check wage was updated
        $updatedWage = EmployeeWage::where('employee_id', $employee->id)->first();
        $this->assertEquals(6000, (float) $updatedWage->basic_salary);
        $this->assertEquals(6500, (float) $updatedWage->total_salary);
        $this->assertEquals(9, (float) $updatedWage->working_hours);
    }

    public function test_edit_and_update_safely_handle_employee_without_preexisting_wage(): void
    {
        // Programmatic creation without wage
        $employee = Employee::create([
            'name' => 'Orphan Wage Employee',
            'email' => 'orphan@example.com',
            'phone' => '5551234567',
            'national_id' => 'NAT555123',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->assertEquals(0, EmployeeWage::where('employee_id', $employee->id)->count());

        // Test edit page loads safely without null pointer exception
        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('employees.edit', $employee->id));
        $editResponse->assertOk();

        // Now test update handles it gracefully
        $updatePayload = [
            'name' => 'Orphan Wage Employee',
            'email' => 'orphan@example.com',
            'phone' => '5551234567',
            'national_id' => 'NAT555123',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'salary_type' => 'weekly',
            'basic_salary' => 1200,
            'bonus_salary' => 100,
            'allowance1' => 0,
            'allowance2' => 0,
            'allowance3' => 0,
            'total_salary' => 1300,
            'working_hours' => 40,
            'overtime_rate' => 15,
            'penalty_late_hour' => 10,
            'penalty_absence_day' => 50,
            'sales_target_settings' => 'no',
        ];

        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('employees.update', $employee->id), $updatePayload);

        $updateResponse->assertSessionHasNoErrors();

        // Exactly one wage should now exist
        $this->assertEquals(1, EmployeeWage::where('employee_id', $employee->id)->count());
        $wage = $employee->fresh()->wage;
        $this->assertNotNull($wage);
        $this->assertEquals('weekly', $wage->salary_type);
        $this->assertEquals(1200, (float) $wage->basic_salary);
    }

    public function test_deleting_employee_removes_associated_wage(): void
    {
        $employee = Employee::create([
            'name' => 'To Delete',
            'email' => 'todelete@example.com',
            'phone' => '9998887776',
            'national_id' => 'NAT999888',
            'status' => 'active',
            'employee_level_id' => $this->employeeLevel->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        EmployeeWage::create([
            'employee_id' => $employee->id,
            'salary_type' => 'monthly',
            'basic_salary' => 3000,
            'total_salary' => 3000,
        ]);

        $this->assertEquals(1, EmployeeWage::where('employee_id', $employee->id)->count());

        $response = $this->actingAs($this->adminUser)
            ->delete(route('employees.destroy', $employee->id));

        $this->assertEquals(0, Employee::where('id', $employee->id)->count());
        $this->assertEquals(0, EmployeeWage::where('employee_id', $employee->id)->count());
    }
}
