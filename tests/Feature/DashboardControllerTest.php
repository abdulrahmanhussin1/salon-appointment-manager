<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
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
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierUser;

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

        $this->cashierUser = User::create([
            'name' => 'Cashier Amy',
            'email' => 'cashier_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        Permission::firstOrCreate(['name' => 'reports.index', 'guard_name' => 'web'], ['group' => 'reports']);
        Permission::firstOrCreate(['name' => 'appointments.index', 'guard_name' => 'web'], ['group' => 'appointments']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $adminRole->givePermissionTo(['reports.index', 'appointments.index']);
        $cashierRole->givePermissionTo('reports.index');

        $this->adminUser->assignRole('admin');
        $this->cashierUser->assignRole('cashier');

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
            'name' => 'Stylist Alpha',
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->employeeB = Employee::create([
            'name' => 'Stylist Beta',
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Link cashier to employee in branch A
        $this->cashierUser->employee_id = $this->employeeA->id;
        $this->cashierUser->save();

        $this->customer = Customer::create([
            'name' => 'Sara Smith',
            'phone' => '0100000000',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashMethod = PaymentMethod::create([
            'name' => 'Cash',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_guest_is_redirected_from_dashboard_endpoints(): void
    {
        $response = $this->getJson(route('dashboard.summary'));
        $this->assertTrue(in_array($response->status(), [401, 302, 403]));
    }

    public function test_summary_returns_aggregated_metrics(): void
    {
        // Create active sales invoice in branch A
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 500,
            'net_total' => 500,
            'paid_amount_cash' => 300,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'payment_method_value' => 200,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Create active expense in branch A
        $expType = ExpenseType::create(['name' => 'Supplies', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        Expense::create([
            'expense_type_id' => $expType->id,
            'paid_amount' => 100,
            'paid_at' => today()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.summary'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'revenue' => ['total', 'cash', 'card', 'services', 'products', 'invoice_count', 'customer_count', 'avg_ticket'],
                    'expenses' => ['total'],
                    'net_profit',
                    'refunds' => ['total', 'count'],
                    'commissions' => ['total'],
                    'customers' => ['new_today', 'total_active'],
                    'appointments' => ['total', 'requested', 'confirmed', 'checked_in', 'in_service', 'completed', 'cancelled', 'no_show'],
                    'top_employee',
                    'top_service',
                ],
                'meta' => ['branch_id', 'period', 'from', 'to', 'generated_at'],
            ]);

        $data = $response->json('data');
        $this->assertEquals(500, $data['revenue']['total']);
        $this->assertEquals(300, $data['revenue']['cash']);
        $this->assertEquals(200, $data['revenue']['card']);
        $this->assertEquals(100, $data['expenses']['total']);
        $this->assertEquals(400, $data['net_profit']); // 500 - 100 = 400
    }

    public function test_summary_respects_branch_filtering(): void
    {
        // Invoice in Branch A
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 400,
            'net_total' => 400,
            'paid_amount_cash' => 400,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Invoice in Branch B
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 600,
            'net_total' => 600,
            'paid_amount_cash' => 600,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Filter for Branch A only
        $resA = $this->actingAs($this->adminUser)->getJson(route('dashboard.summary', ['branch_id' => $this->branchA->id]));
        $resA->assertOk();
        $this->assertEquals(400, $resA->json('data.revenue.total'));

        // Filter for Branch B only
        $resB = $this->actingAs($this->adminUser)->getJson(route('dashboard.summary', ['branch_id' => $this->branchB->id]));
        $resB->assertOk();
        $this->assertEquals(600, $resB->json('data.revenue.total'));

        // All branches
        $resAll = $this->actingAs($this->adminUser)->getJson(route('dashboard.summary'));
        $resAll->assertOk();
        $this->assertEquals(1000, $resAll->json('data.revenue.total'));
    }

    public function test_cashier_is_scoped_to_assigned_branch(): void
    {
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 250,
            'net_total' => 250,
            'paid_amount_cash' => 250,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 750,
            'net_total' => 750,
            'paid_amount_cash' => 750,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Cashier Amy belongs to Branch A, even if requesting Branch B, she is locked to Branch A
        $response = $this->actingAs($this->cashierUser)->getJson(route('dashboard.summary', ['branch_id' => $this->branchB->id]));
        $response->assertOk();
        $this->assertEquals(250, $response->json('data.revenue.total'));
        $this->assertEquals($this->branchA->id, $response->json('meta.branch_id'));
    }

    public function test_revenue_endpoint_returns_continuous_time_series(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.revenue', ['period' => 'today']));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['labels', 'services', 'products', 'total'],
                'meta' => ['branch_id', 'from', 'to'],
            ]);

        $this->assertNotEmpty($response->json('data.labels'));
        $this->assertCount(count($response->json('data.labels')), $response->json('data.total'));
    }

    public function test_appointments_endpoint_returns_breakdown_and_actionable_list(): void
    {
        $serviceCat = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Hair Cut',
            'price' => 150,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        Appointment::create([
            'start_date' => today()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'end_date' => today()->setTime(10, 30)->format('Y-m-d H:i:s'),
            'customer_id' => $this->customer->id,
            'provider_id' => $this->employeeA->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::REQUESTED->value,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.appointments', ['date' => today()->toDateString()]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'status_breakdown',
                    'appointments' => [
                        '*' => ['id', 'start_time', 'end_time', 'customer_name', 'service_name', 'provider_name', 'status', 'can_confirm', 'can_check_in', 'can_complete', 'can_cancel'],
                    ],
                ],
                'meta' => ['branch_id', 'date', 'total_count'],
            ]);

        $this->assertEquals(1, $response->json('data.status_breakdown.requested'));
        $first = $response->json('data.appointments.0');
        $this->assertTrue($first['can_confirm']);
        $this->assertFalse($first['can_check_in']);
    }

    public function test_inventory_alerts_returns_out_of_stock_and_low_stock(): void
    {
        $inv = Inventory::create([
            'name' => 'Main Stock',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $cat = ProductCategory::create(['name' => 'Retail', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $prodOOS = Product::create(['name' => 'Zero Stock Oil', 'price' => 50, 'branch_id' => $this->branchA->id, 'product_category_id' => $cat->id, 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $prodLow = Product::create(['name' => 'Low Stock Shampoo', 'price' => 80, 'branch_id' => $this->branchA->id, 'product_category_id' => $cat->id, 'status' => 'active', 'created_by' => $this->adminUser->id]);

        InventoryProduct::create(['inventory_id' => $inv->id, 'product_id' => $prodOOS->id, 'quantity' => 0]);
        InventoryProduct::create(['inventory_id' => $inv->id, 'product_id' => $prodLow->id, 'quantity' => 3]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.inventory_alerts'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['out_of_stock', 'low_stock', 'out_of_stock_count', 'low_stock_count'],
                'meta' => ['branch_id'],
            ]);

        $this->assertEquals(1, $response->json('data.out_of_stock_count'));
        $this->assertEquals(1, $response->json('data.low_stock_count'));
    }

    public function test_expenses_endpoint_returns_total_and_categories(): void
    {
        $expType = ExpenseType::create(['name' => 'Electric', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        Expense::create([
            'expense_type_id' => $expType->id,
            'paid_amount' => 250,
            'paid_at' => today()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.expenses'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'by_category' => [
                        '*' => ['category', 'total', 'percentage'],
                    ],
                ],
                'meta',
            ]);

        $this->assertEquals(250, $response->json('data.total'));
        $this->assertEquals('Electric', $response->json('data.by_category.0.category'));
    }

    public function test_activity_endpoint_returns_sorted_timeline(): void
    {
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 150,
            'net_total' => 150,
            'paid_amount_cash' => 150,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.activity'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'title', 'description', 'url', 'timestamp', 'time_ago'],
                ],
                'meta',
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_alerts_endpoint_returns_prioritized_operational_warnings(): void
    {
        $serviceCat = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Hair Trim',
            'price' => 100,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        Appointment::create([
            'start_date' => today()->setTime(14, 0)->format('Y-m-d H:i:s'),
            'end_date' => today()->setTime(14, 30)->format('Y-m-d H:i:s'),
            'customer_id' => $this->customer->id,
            'provider_id' => $this->employeeA->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::REQUESTED->value,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.alerts'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'severity', 'title', 'body', 'count', 'action_label', 'action_url'],
                ],
                'meta',
            ]);

        $types = collect($response->json('data'))->pluck('type')->toArray();
        $this->assertContains('unconfirmed_appointments', $types);
    }

    public function test_staff_endpoint_returns_ranking_and_commissions(): void
    {
        $serviceCat = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Hair Trim',
            'price' => 100,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 200,
            'net_total' => 200,
            'paid_amount_cash' => 200,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeA->id,
            'customer_price' => 100,
            'quantity' => 2,
            'subtotal' => 200,
            'commission_amount' => 20,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.staff'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['employee_id', 'name', 'branch', 'service_count', 'revenue', 'commission'],
                ],
                'meta',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($this->employeeA->name, $data[0]['name']);
        $this->assertEquals(200, $data[0]['revenue']);
        $this->assertEquals(20, $data[0]['commission']);
    }

    public function test_summary_computes_service_and_product_split_accurately(): void
    {
        $serviceCat = ServiceCategory::create(['name' => 'Spa', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Facial',
            'price' => 300,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $inv = Inventory::create(['name' => 'Spa Inv', 'branch_id' => $this->branchA->id, 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $prodCat = ProductCategory::create(['name' => 'Cream', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $product = Product::create([
            'name' => 'Face Cream',
            'price' => 150,
            'category_id' => $prodCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 450,
            'net_total' => 450,
            'paid_amount_cash' => 450,
            'balance_due' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'provider_id' => $this->employeeA->id,
            'customer_price' => 300,
            'quantity' => 1,
            'subtotal' => 300,
            'commission_amount' => 30,
        ]);

        \App\Models\SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'provider_id' => $this->employeeA->id,
            'customer_price' => 150,
            'quantity' => 1,
            'subtotal' => 150,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.summary'));
        $response->assertOk();

        $this->assertEquals(300, $response->json('data.revenue.services'));
        $this->assertEquals(150, $response->json('data.revenue.products'));
        $this->assertEquals(450, $response->json('data.revenue.total'));
    }

    public function test_revenue_handles_empty_date_range_with_zeros(): void
    {
        $pastDate = today()->subMonths(6);
        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.revenue', [
            'period' => 'custom',
            'from' => $pastDate->copy()->startOfWeek()->toDateString(),
            'to' => $pastDate->copy()->endOfWeek()->toDateString(),
        ]));

        $response->assertOk();
        $totals = $response->json('data.total');
        $this->assertNotEmpty($totals);
        foreach ($totals as $val) {
            $this->assertEquals(0, $val);
        }
    }

    public function test_provider_only_sees_own_appointments_in_dashboard(): void
    {
        $providerRole = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        $providerUser = User::create([
            'name' => 'Provider Stylist',
            'email' => 'provider_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $providerUser->assignRole('provider');
        $providerUser->employee_id = $this->employeeA->id;
        $providerUser->save();

        $serviceCat = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $service = Service::create([
            'name' => 'Hair Cut',
            'price' => 150,
            'service_category_id' => $serviceCat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Appointment for Employee A
        Appointment::create([
            'start_date' => today()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'end_date' => today()->setTime(10, 30)->format('Y-m-d H:i:s'),
            'customer_id' => $this->customer->id,
            'provider_id' => $this->employeeA->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->adminUser->id,
        ]);

        // Appointment for Employee B
        Appointment::create([
            'start_date' => today()->setTime(11, 0)->format('Y-m-d H:i:s'),
            'end_date' => today()->setTime(11, 30)->format('Y-m-d H:i:s'),
            'customer_id' => $this->customer->id,
            'provider_id' => $this->employeeB->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($providerUser)->getJson(route('dashboard.appointments'));
        $response->assertOk();

        $appts = $response->json('data.appointments');
        $this->assertCount(1, $appts);
        $this->assertEquals($this->employeeA->name, $appts[0]['provider_name']);
    }

    public function test_alerts_are_sorted_by_critical_warning_info_severity(): void
    {
        // 1. Critical: out of stock
        $inv = Inventory::create(['name' => 'Stock Inv', 'branch_id' => $this->branchA->id, 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $prodCat = ProductCategory::create(['name' => 'Hair Care', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $prodOOS = Product::create(['name' => 'Serum', 'price' => 200, 'category_id' => $prodCat->id, 'branch_id' => $this->branchA->id, 'status' => 'active', 'created_by' => $this->adminUser->id]);
        InventoryProduct::create(['inventory_id' => $inv->id, 'product_id' => $prodOOS->id, 'quantity' => 0]);

        // 2. Info: invoice with balance due
        SalesInvoice::create([
            'invoice_date' => today()->toDateString(),
            'total_amount' => 500,
            'net_total' => 500,
            'paid_amount_cash' => 200,
            'balance_due' => 300,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('dashboard.alerts'));
        $response->assertOk();

        $severities = collect($response->json('data'))->pluck('severity')->toArray();
        $this->assertContains('critical', $severities);
        $this->assertContains('info', $severities);
        // Critical must appear before Info
        $critIdx = array_search('critical', $severities);
        $infoIdx = array_search('info', $severities);
        $this->assertLessThan($infoIdx, $critIdx);
    }
}
