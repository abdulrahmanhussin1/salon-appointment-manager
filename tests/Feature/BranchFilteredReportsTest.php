<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchFilteredReportsTest extends TestCase
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

        Permission::firstOrCreate(
            ['name' => 'reports.index', 'guard_name' => 'web'],
            ['group' => 'reports']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $adminRole->givePermissionTo('reports.index');
        $cashierRole->givePermissionTo('reports.index');

        $this->adminUser->assignRole('admin');

        $this->branchA = Branch::create([
            'name' => 'Branch Alpha',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Branch Beta',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->employeeA = Employee::create([
            'name' => 'Staff Alpha',
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->employeeB = Employee::create([
            'name' => 'Staff Beta',
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '0123456789',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashierA = User::create([
            'name' => 'Cashier Alpha',
            'email' => 'cashier_a_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeA->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierA->assignRole('cashier');

        $this->cashierB = User::create([
            'name' => 'Cashier Beta',
            'email' => 'cashier_b_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeB->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierB->assignRole('cashier');

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['name' => 'cash'],
            ['status' => 'active', 'created_by' => $this->adminUser->id]
        );
    }

    protected function createInvoice(array $attributes): SalesInvoice
    {
        return SalesInvoice::create(array_merge([
            'customer_id' => $this->customer->id,
            'invoice_discount' => 0,
            'invoice_deposit' => 0,
            'invoice_tax' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'payment_method_value' => 0,
            'balance_due' => 0,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ], $attributes));
    }

    public function test_admin_can_view_daily_revenues_for_all_branches()
    {
        $today = now()->toDateString();

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 500,
            'net_total' => 500,
            'paid_amount_cash' => 500,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 300,
            'net_total' => 300,
            'paid_amount_cash' => 300,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        $expenseType = ExpenseType::create(['name' => 'Utilities', 'status' => 'active', 'created_by' => $this->adminUser->id]);

        Expense::create([
            'expense_type_id' => $expenseType->id,
            'amount' => 50,
            'paid_amount' => 50,
            'paid_at' => now(),
            'payment_method_id' => $this->cashMethod->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->cashierA->id,
        ]);

        Expense::create([
            'expense_type_id' => $expenseType->id,
            'amount' => 20,
            'paid_amount' => 20,
            'paid_at' => now(),
            'payment_method_id' => $this->cashMethod->id,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->cashierB->id,
        ]);

        // Admin requests all branches
        $responseAll = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));

        $responseAll->assertOk();
        $this->assertEquals(800, $responseAll->json('total_cash_revenue'));
        $this->assertEquals(70, $responseAll->json('total_other_expenses'));
    }

    public function test_admin_can_filter_daily_revenues_by_specific_branch()
    {
        $today = now()->toDateString();

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 500,
            'net_total' => 500,
            'paid_amount_cash' => 500,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 300,
            'net_total' => 300,
            'paid_amount_cash' => 300,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        // Admin filters specifically to Branch A
        $responseA = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchA->id,
        ]));

        $responseA->assertOk();
        $this->assertEquals(500, $responseA->json('total_cash_revenue'));

        // Admin filters specifically to Branch B
        $responseB = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchB->id,
        ]));

        $responseB->assertOk();
        $this->assertEquals(300, $responseB->json('total_cash_revenue'));
    }

    public function test_cashier_is_strictly_auto_filtered_to_own_branch_and_cannot_override()
    {
        $today = now()->toDateString();

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 500,
            'net_total' => 500,
            'paid_amount_cash' => 500,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 300,
            'net_total' => 300,
            'paid_amount_cash' => 300,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        // Cashier A makes request with NO branch filter
        $responseNoBranch = $this->actingAs($this->cashierA)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
        ]));
        $responseNoBranch->assertOk();
        $this->assertEquals(500, $responseNoBranch->json('total_cash_revenue'));

        // Cashier A attempts to override by passing Branch B's ID
        $responseOverrideB = $this->actingAs($this->cashierA)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchB->id,
        ]));
        $responseOverrideB->assertOk();
        // Server ignores requested branch_id and stays strictly scoped to Branch A
        $this->assertEquals(500, $responseOverrideB->json('total_cash_revenue'));

        // Cashier A attempts to override by passing 'all'
        $responseOverrideAll = $this->actingAs($this->cashierA)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => 'all',
        ]));
        $responseOverrideAll->assertOk();
        $this->assertEquals(500, $responseOverrideAll->json('total_cash_revenue'));
    }

    public function test_total_daily_revenues_filters_by_branch_for_admin_and_cashier()
    {
        $today = now()->toDateString();

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 700,
            'net_total' => 700,
            'paid_amount_cash' => 700,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 400,
            'net_total' => 400,
            'paid_amount_cash' => 400,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        // Admin filtered to Branch A
        $responseAdminA = $this->actingAs($this->adminUser)->postJson(route('report.TotalDailyRevenues'), [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchA->id,
        ]);
        $responseAdminA->assertOk();
        $dataA = $responseAdminA->json('data');
        $this->assertNotEmpty($dataA);
        $this->assertEquals(700, $dataA[0]['total']);

        // Cashier A attempting to request Branch B
        $responseCashier = $this->actingAs($this->cashierA)->postJson(route('report.TotalDailyRevenues'), [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchB->id,
        ]);
        $responseCashier->assertOk();
        $dataCashier = $responseCashier->json('data');
        $this->assertNotEmpty($dataCashier);
        // Scoped strictly to Cashier A's branch
        $this->assertEquals(700, $dataCashier[0]['total']);
    }

    public function test_daily_summary_filters_by_branch_for_admin_and_cashier()
    {
        $today = now()->toDateString();

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 1000,
            'net_total' => 1000,
            'paid_amount_cash' => 1000,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 2000,
            'net_total' => 2000,
            'paid_amount_cash' => 2000,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        // Admin filtered to Branch B
        $responseAdminB = $this->actingAs($this->adminUser)->postJson(route('report.dailySummary'), [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchB->id,
        ]);
        $responseAdminB->assertOk();
        $dataB = $responseAdminB->json('data');
        $this->assertEquals(1, $dataB[0]['total_customers']);

        // Cashier A attempting to request Branch B
        $responseCashier = $this->actingAs($this->cashierA)->postJson(route('report.dailySummary'), [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchB->id,
        ]);
        $responseCashier->assertOk();
        $dataCashier = $responseCashier->json('data');
        // Cashier A sees 1 customer from Branch A
        $this->assertEquals(1, $dataCashier[0]['total_customers']);
    }

    public function test_monthly_summary_filters_by_branch_for_admin_and_cashier()
    {
        $year = now()->year;

        $invoiceA = $this->createInvoice([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 1500,
            'net_total' => 1500,
            'paid_amount_cash' => 1500,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $serviceCat = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Cut & Style',
            'price' => 1500,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoiceA->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeA->id,
            'quantity' => 1,
            'customer_price' => 1500,
            'subtotal' => 1500,
            'total_amount' => 1500,
            'created_by' => $this->cashierA->id,
        ]);

        $invoiceB = $this->createInvoice([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 2500,
            'net_total' => 2500,
            'paid_amount_cash' => 2500,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoiceB->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeB->id,
            'quantity' => 1,
            'customer_price' => 2500,
            'subtotal' => 2500,
            'total_amount' => 2500,
            'created_by' => $this->cashierB->id,
        ]);

        // Admin filters to Branch A
        $responseAdminA = $this->actingAs($this->adminUser)->getJson(route('report.monthlySummary', [
            'year' => $year,
            'branch_id' => $this->branchA->id,
        ]));
        $responseAdminA->assertOk();
        $monthDataA = collect($responseAdminA->json('data'))->firstWhere('metric', now()->format('F'));
        $this->assertEquals('1,500.00', $monthDataA['services']);

        // Cashier A attempts to override to Branch B
        $responseCashier = $this->actingAs($this->cashierA)->getJson(route('report.monthlySummary', [
            'year' => $year,
            'branch_id' => $this->branchB->id,
        ]));
        $responseCashier->assertOk();
        $monthDataCashier = collect($responseCashier->json('data'))->firstWhere('metric', now()->format('F'));
        $this->assertEquals('1,500.00', $monthDataCashier['services']);
    }

    public function test_employee_reports_filter_by_branch()
    {
        $today = now()->toDateString();

        $invoiceA = $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 500,
            'net_total' => 500,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->cashierA->id,
        ]);

        $serviceCat = ServiceCategory::create(['name' => 'Spa', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Massage',
            'price' => 500,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoiceA->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeA->id,
            'quantity' => 1,
            'customer_price' => 500,
            'subtotal' => 500,
            'total_amount' => 500,
            'created_by' => $this->cashierA->id,
        ]);

        $invoiceB = $this->createInvoice([
            'invoice_date' => $today,
            'total_amount' => 800,
            'net_total' => 800,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->cashierB->id,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoiceB->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeB->id,
            'quantity' => 1,
            'customer_price' => 800,
            'subtotal' => 800,
            'total_amount' => 800,
            'created_by' => $this->cashierB->id,
        ]);

        // Employee summary stats for Admin Branch A
        $resSummaryStatsA = $this->actingAs($this->adminUser)->getJson(route('report.employee-summary-services.stats', [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchA->id,
        ]));
        $resSummaryStatsA->assertOk();
        $this->assertEquals(500, $resSummaryStatsA->json('total_amount'));

        // Employee summary stats for Cashier B (forced to Branch B)
        $resCashierStats = $this->actingAs($this->cashierB)->getJson(route('report.employee-summary-services.stats', [
            'start_date' => $today,
            'end_date' => $today,
            'branch_id' => $this->branchA->id, // Attempt to override
        ]));
        $resCashierStats->assertOk();
        $this->assertEquals(800, $resCashierStats->json('total_amount'));
    }

    public function test_stock_report_and_balance_report_filter_by_branch()
    {
        $supplier = Supplier::create(['name' => 'Hair Inc', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $unit = Unit::create(['name' => 'Bottle', 'status' => 'active', 'branch_id' => $this->branchA->id, 'created_by' => $this->adminUser->id]);
        $cat = ProductCategory::create(['name' => 'Hair Products', 'status' => 'active', 'created_by' => $this->adminUser->id]);

        $invA = Inventory::create([
            'name' => 'Alpha Inventory',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invB = Inventory::create([
            'name' => 'Beta Inventory',
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $productA = Product::create([
            'name' => 'Shampoo A',
            'category_id' => $cat->id,
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'initial_quantity' => 10,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        InventoryProduct::create([
            'inventory_id' => $invA->id,
            'product_id' => $productA->id,
            'quantity' => 10,
            'created_by' => $this->adminUser->id,
        ]);

        $productB = Product::create([
            'name' => 'Conditioner B',
            'category_id' => $cat->id,
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'initial_quantity' => 25,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        InventoryProduct::create([
            'inventory_id' => $invB->id,
            'product_id' => $productB->id,
            'quantity' => 25,
            'created_by' => $this->adminUser->id,
        ]);

        // Stock report - Admin filtered to Branch A
        $resStockAdminA = $this->actingAs($this->adminUser)->getJson(route('report.stock_report', [
            'branch_id' => $this->branchA->id,
        ]));
        $resStockAdminA->assertOk();
        $productsInA = collect($resStockAdminA->json('data'))->pluck('name')->toArray();
        $this->assertContains('Shampoo A', $productsInA);
        $this->assertNotContains('Conditioner B', $productsInA);

        // Stock report - Cashier A forced to Branch A
        $resStockCashierA = $this->actingAs($this->cashierA)->getJson(route('report.stock_report', [
            'branch_id' => $this->branchB->id, // attempt override
        ]));
        $resStockCashierA->assertOk();
        $productsInCashier = collect($resStockCashierA->json('data'))->pluck('name')->toArray();
        $this->assertContains('Shampoo A', $productsInCashier);
        $this->assertNotContains('Conditioner B', $productsInCashier);
    }
}
