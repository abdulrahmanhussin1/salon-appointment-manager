<?php

namespace Tests\Feature;

use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DailyRevenueReportExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierUserA;

    protected Branch $branchA;

    protected Branch $branchB;

    protected PaymentMethod $cashMethod;

    protected PaymentMethod $cardMethod;

    protected PaymentMethod $bankMethod;

    protected ExpenseType $expenseType;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'report.daily_revenues',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => 'reports',
            ]);
        }

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->adminUser->givePermissionTo($permissions);
        $this->adminUser->assignRole('admin');

        $this->branchA = Branch::create([
            'name' => 'Branch A',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Branch B',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Cashier Level',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $employeeA = Employee::create([
            'name' => 'Employee A',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashierUserA = User::create([
            'name' => 'Cashier A',
            'email' => 'cashier_a_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $employeeA->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierUserA->givePermissionTo($permissions);
        $this->cashierUserA->assignRole('cashier');

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'created_by' => $this->adminUser->id,
        ]);

        \Illuminate\Support\Facades\View::share('adminPanelSetting', AdminPanelSetting::first());

        $this->cashMethod = PaymentMethod::create([
            'name' => 'cash',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->cardMethod = PaymentMethod::create([
            'name' => 'visa',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->bankMethod = PaymentMethod::create([
            'name' => 'bank_transfer',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->expenseType = ExpenseType::create([
            'name' => 'Salon Operations',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_daily_revenue_report_sums_all_active_expenses_across_all_payment_methods(): void
    {
        $today = now()->toDateString();

        // 1. Cash expense: 100
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 100,
            'paid_amount' => 100,
            'paid_at' => now(),
            'payment_method_id' => $this->cashMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // 2. Visa card expense: 150
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 150,
            'paid_amount' => 150,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // 3. Bank transfer expense: 250
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 250,
            'paid_amount' => 250,
            'paid_at' => now(),
            'payment_method_id' => $this->bankMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));

        $response->assertOk();

        // REQ-016: Total expenses must equal 100 + 150 + 250 = 500 (not just 100 cash)
        $this->assertEquals(500.00, (float) $response->json('total_other_expenses'));
        $this->assertEquals(100.00, (float) $response->json('total_cash_expenses'));
        $this->assertEquals(400.00, (float) $response->json('total_non_cash_expenses'));
    }

    public function test_daily_revenue_report_excludes_inactive_expenses(): void
    {
        $today = now()->toDateString();

        // Active expense: 100
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 100,
            'paid_amount' => 100,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Inactive expense: 300 (should be excluded)
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 300,
            'paid_amount' => 300,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'inactive',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));

        $response->assertOk();
        $this->assertEquals(100.00, (float) $response->json('total_other_expenses'));
    }

    public function test_daily_revenue_report_excludes_expenses_outside_date_range(): void
    {
        $today = now()->toDateString();

        // Expense today: 120
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 120,
            'paid_amount' => 120,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Expense yesterday: 200 (outside range)
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 200,
            'paid_amount' => 200,
            'paid_at' => now()->subDays(2),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));

        $response->assertOk();
        $this->assertEquals(120.00, (float) $response->json('total_other_expenses'));
    }

    public function test_daily_revenue_report_filters_expenses_by_branch(): void
    {
        $today = now()->toDateString();

        // Branch A expense: 150 (non-cash)
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 150,
            'paid_amount' => 150,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Branch B expense: 350 (non-cash)
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 350,
            'paid_amount' => 350,
            'paid_at' => now(),
            'payment_method_id' => $this->bankMethod->id,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Filter specifically to Branch A
        $responseA = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchA->id,
        ]));
        $responseA->assertOk();
        $this->assertEquals(150.00, (float) $responseA->json('total_other_expenses'));

        // Filter to all branches
        $responseAll = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));
        $responseAll->assertOk();
        $this->assertEquals(500.00, (float) $responseAll->json('total_other_expenses'));
    }

    public function test_cashier_expense_filtering_locked_to_own_branch(): void
    {
        $today = now()->toDateString();

        // Branch A expense: 80
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 80,
            'paid_amount' => 80,
            'paid_at' => now(),
            'payment_method_id' => $this->cardMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Branch B expense: 120
        Expense::create([
            'expense_type_id' => $this->expenseType->id,
            'amount' => 120,
            'paid_amount' => 120,
            'paid_at' => now(),
            'payment_method_id' => $this->bankMethod->id,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Cashier A requesting Branch B should still be locked to Branch A (80)
        $response = $this->actingAs($this->cashierUserA)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchB->id,
        ]));

        $response->assertOk();
        $this->assertEquals(80.00, (float) $response->json('total_other_expenses'));
    }
}
