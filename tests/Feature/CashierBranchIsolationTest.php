<?php

namespace Tests\Feature;

use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for GAP-001: Cashier branch isolation on invoice creation.
 *
 * NFR-003 / BR-006: A cashier must not be able to create an invoice
 * for a branch other than their own, even if they supply a foreign
 * branch_id in the request body.
 */
class CashierBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierA;

    protected User $cashierB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected Employee $employeeA;

    protected Employee $employeeB;

    protected Customer $customer;

    protected PaymentMethod $cashMethod;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'block_insufficient_consumables' => false,
            'void_time_window_hours' => 24,
        ]);

        $permissions = [
            'sales_invoices.index',
            'sales_invoices.create',
            'sales_invoices.show',
            'sales_invoices.edit',
            'sales_invoices.destroy',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => 'sales_invoices',
            ]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $adminRole->givePermissionTo($permissions);
        $cashierRole->givePermissionTo($permissions);

        $this->adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->adminUser->assignRole('admin');

        $this->branchA = Branch::create(['name' => 'Branch Alpha', 'status' => 'active', 'created_by' => 1]);
        $this->branchB = Branch::create(['name' => 'Branch Beta',  'status' => 'active', 'created_by' => 1]);

        $level = EmployeeLevel::create(['name' => 'Stylist', 'status' => 'active', 'created_by' => 1]);

        $this->employeeA = Employee::create([
            'name' => 'Staff Alpha', 'status' => 'active',
            'branch_id' => $this->branchA->id, 'employee_level_id' => $level->id, 'created_by' => 1,
        ]);

        $this->employeeB = Employee::create([
            'name' => 'Staff Beta', 'status' => 'active',
            'branch_id' => $this->branchB->id, 'employee_level_id' => $level->id, 'created_by' => 1,
        ]);

        $this->cashierA = User::create([
            'name' => 'Cashier Alpha', 'email' => 'cashier_a_'.uniqid().'@example.com',
            'email_verified_at' => now(), 'password' => bcrypt('password'),
            'status' => 'active', 'employee_id' => $this->employeeA->id, 'created_by' => 1,
        ]);
        $this->cashierA->assignRole('cashier');

        $this->cashierB = User::create([
            'name' => 'Cashier Beta', 'email' => 'cashier_b_'.uniqid().'@example.com',
            'email_verified_at' => now(), 'password' => bcrypt('password'),
            'status' => 'active', 'employee_id' => $this->employeeB->id, 'created_by' => 1,
        ]);
        $this->cashierB->assignRole('cashier');

        $this->customer = Customer::create([
            'name' => 'John Customer', 'phone' => '0123456789',
            'status' => 'active', 'created_by' => 1,
        ]);

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['name' => 'cash'],
            ['status' => 'active', 'created_by' => 1]
        );

        $serviceCategory = ServiceCategory::create([
            'name' => 'Hair', 'status' => 'active', 'created_by' => 1,
        ]);

        $this->service = Service::create([
            'name' => 'Cut', 'duration' => 30, 'price' => 100,
            'price_can_change' => false,
            'service_category_id' => $serviceCategory->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active', 'created_by' => 1,
        ]);

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager', 'system_logo' => null,
            'block_insufficient_consumables' => false,
            'void_time_window_hours' => 24, 'created_by' => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function buildPayload(int $branchId, int $providerId): array
    {
        return [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $branchId,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'cash_payment' => 100,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [[
                'type' => 'service',
                'item_id' => $this->service->id,
                'code' => 'CUT-001',
                'price' => 100,
                'quantity' => 1,
                'provider_id' => $providerId,
                'discount' => 0,
                'tax' => 0,
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Cashier A can successfully create an invoice for their own branch (Branch A).
     * The branch check must not block legitimate own-branch submissions.
     */
    public function test_cashier_can_create_invoice_for_own_branch(): void
    {
        $response = $this->actingAs($this->cashierA)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchA->id, $this->employeeA->id));

        // May succeed (200) or return warnings — must NOT be 403
        $this->assertNotEquals(403, $response->status(), 'Cashier was incorrectly blocked from their own branch.');
    }

    /**
     * Cashier A is rejected with 403 when they attempt to create an invoice
     * for Branch B by manipulating the branch_id in the POST body.
     *
     * Validates NFR-003 and BR-006 server-side enforcement (GAP-001).
     */
    public function test_cashier_cannot_create_invoice_for_another_branch(): void
    {
        $response = $this->actingAs($this->cashierA)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchB->id, $this->employeeA->id));

        $response->assertStatus(403);
    }

    /**
     * Cashier B is similarly rejected when targeting Branch A.
     */
    public function test_cashier_b_cannot_spoof_branch_a(): void
    {
        $response = $this->actingAs($this->cashierB)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchA->id, $this->employeeB->id));

        $response->assertStatus(403);
    }

    /**
     * An admin can create an invoice for any branch without restriction.
     * The cashier-only guard must not block admin users.
     */
    public function test_admin_can_create_invoice_for_any_branch(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchA->id, $this->employeeA->id));

        $this->assertNotEquals(403, $response->status(), 'Admin was incorrectly blocked from creating an invoice.');
    }

    /**
     * The 403 response body contains the expected error message so the
     * client-side code can surface a meaningful explanation.
     */
    public function test_cross_branch_attempt_returns_clear_error_message(): void
    {
        $response = $this->actingAs($this->cashierA)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchB->id, $this->employeeA->id));

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'You are not authorized to create invoices for another branch.']);
    }

    /**
     * The blocked attempt must not persist any invoice record.
     */
    public function test_blocked_attempt_does_not_create_invoice(): void
    {
        $this->actingAs($this->cashierA)
            ->postJson(route('sales_invoices.store'), $this->buildPayload($this->branchB->id, $this->employeeA->id));

        $this->assertDatabaseEmpty('sales_invoices');
    }
}
