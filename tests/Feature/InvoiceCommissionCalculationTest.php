<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceCommissionCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Branch $branch;

    protected Customer $customer;

    protected Employee $provider;

    protected PaymentMethod $paymentMethod;

    protected ServiceCategory $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'sales_invoices.index',
            'sales_invoices.create',
            'sales_invoices.show',
            'reports.index',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => 'sales_invoices',
            ]);
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
            'name' => 'Main Salon',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Alice Customer',
            'phone' => '1112223334',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $employeeLevel = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Stylist Sarah',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'employee_level_id' => $employeeLevel->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Hair Treatments',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_percentage_commission_calculated_and_persisted_correctly_on_sales_invoice_creation(): void
    {
        $service = Service::create([
            'name' => 'Hair Coloring',
            'price' => 100.00,
            'price_can_change' => false,
            'duration' => 60,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Attach provider with 15% commission, deferred
        DB::table('service_employees')->insert([
            'service_id' => $service->id,
            'employee_id' => $this->provider->id,
            'commission_type' => 'percentage',
            'commission_value' => 15.00,
            'is_immediate_commission' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1 service with 10% discount: net price = 90.00. 15% of 90.00 = 13.50 commission.
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 90.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-001',
                    'price' => 100.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 10,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoiceId)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('percentage', $detail->commission_type);
        $this->assertEquals(15.00, (float) $detail->commission_rate);
        $this->assertEquals(13.50, (float) $detail->commission_amount);
        $this->assertFalse((bool) $detail->is_immediate_commission);
    }

    public function test_fixed_value_commission_multiplied_by_quantity_and_persisted(): void
    {
        $service = Service::create([
            'name' => 'Hair Wash & Blowdry',
            'price' => 50.00,
            'price_can_change' => false,
            'duration' => 30,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Attach provider with $12.50 fixed commission per unit, immediate
        DB::table('service_employees')->insert([
            'service_id' => $service->id,
            'employee_id' => $this->provider->id,
            'commission_type' => 'value',
            'commission_value' => 12.50,
            'is_immediate_commission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3 quantities: commission = 12.50 * 3 = 37.50
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 150.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-002',
                    'price' => 50.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 3,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoiceId)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('value', $detail->commission_type);
        $this->assertEquals(12.50, (float) $detail->commission_rate);
        $this->assertEquals(37.50, (float) $detail->commission_amount);
        $this->assertTrue((bool) $detail->is_immediate_commission);
    }

    public function test_service_without_pivot_configuration_has_zero_commission(): void
    {
        $service = Service::create([
            'name' => 'Consultation',
            'price' => 30.00,
            'price_can_change' => false,
            'duration' => 20,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        // No record in service_employees pivot table

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 30.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-003',
                    'price' => 30.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoiceId)->first();
        $this->assertNotNull($detail);
        $this->assertNull($detail->commission_type);
        $this->assertEquals(0.00, (float) $detail->commission_rate);
        $this->assertEquals(0.00, (float) $detail->commission_amount);
        $this->assertFalse((bool) $detail->is_immediate_commission);
    }

    public function test_custom_price_service_computes_percentage_commission_on_custom_price(): void
    {
        $service = Service::create([
            'name' => 'Bridal Styling',
            'price' => 100.00,
            'price_can_change' => true,
            'duration' => 90,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        DB::table('service_employees')->insert([
            'service_id' => $service->id,
            'employee_id' => $this->provider->id,
            'commission_type' => 'percentage',
            'commission_value' => 20.00,
            'is_immediate_commission' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Custom price: $250.00. 20% commission on 250.00 = 50.00.
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 250.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-004',
                    'price' => 250.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoiceId)->first();
        $this->assertEquals(250.00, (float) $detail->customer_price);
        $this->assertEquals(20.00, (float) $detail->commission_rate);
        $this->assertEquals(50.00, (float) $detail->commission_amount);
    }

    public function test_product_invoice_item_has_zero_commission(): void
    {
        $unit = Unit::create([
            'name' => 'Bottle',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $category = ProductCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $product = Product::create([
            'name' => 'Argan Oil Shampoo',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'unit_id' => $unit->id,
            'product_category_id' => $category->id,
            'created_by' => $this->adminUser->id,
        ]);

        $inventory = \App\Models\Inventory::create([
            'name' => 'Main Inventory',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\InventoryProduct::create([
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Co',
            'created_by' => $this->adminUser->id,
        ]);

        $purchaseInvoice = \App\Models\PurchaseInvoice::create([
            'invoice_number' => 1001,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 500,
            'status' => 'active',
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'supplier_price' => 10.00,
            'customer_price' => 25.00,
            'quantity' => 50,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 25.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'product',
                    'item_id' => $product->id,
                    'code' => 'PRD-001',
                    'price' => 25.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoiceId)->first();
        $this->assertNotNull($detail);
        $this->assertEquals(0.00, (float) $detail->commission_amount);
        $this->assertNull($detail->commission_type);
        $this->assertFalse((bool) $detail->is_immediate_commission);
    }

    public function test_daily_summary_report_uses_calculated_commission_amount_sum(): void
    {
        $service = Service::create([
            'name' => 'Haircut & Styling',
            'price' => 100.00,
            'price_can_change' => false,
            'duration' => 45,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        DB::table('service_employees')->insert([
            'service_id' => $service->id,
            'employee_id' => $this->provider->id,
            'commission_type' => 'percentage',
            'commission_value' => 25.00,
            'is_immediate_commission' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 100.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-005',
                    'price' => 100.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $createResponse = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);

        // Call Daily Summary report
        $reportResponse = $this->actingAs($this->adminUser)
            ->postJson(route('report.dailySummary'), [
                'start_date' => '2026-09-24',
                'end_date' => '2026-09-24',
            ]);

        $reportResponse->assertStatus(200);
        $data = $reportResponse->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        // services_sales is 100.00
        $this->assertEquals(100.00, (float) $row['services_sales']);
        // BUG-013 fix: services_commissions must be 25.00 (the computed commission amount), NOT 100.00 (gross sales)
        $this->assertEquals(25.00, (float) $row['services_commissions']);
    }

    public function test_employee_services_report_aggregates_commission_correctly(): void
    {
        $service = Service::create([
            'name' => 'Haircut',
            'price' => 80.00,
            'price_can_change' => false,
            'duration' => 30,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        DB::table('service_employees')->insert([
            'service_id' => $service->id,
            'employee_id' => $this->provider->id,
            'commission_type' => 'percentage',
            'commission_value' => 10.00,
            'is_immediate_commission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-09-24',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'deposit' => 0,
            'cash_payment' => 80.00,
            'payment_method_value' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-006',
                    'price' => 80.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $createResponse = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);

        // Check getEmployeeStats endpoint
        $statsResponse = $this->actingAs($this->adminUser)
            ->getJson(route('report.employee-services.stats', [
                'start_date' => '2026-09-24',
                'end_date' => '2026-09-24',
            ]));

        $statsResponse->assertStatus(200);
        $stats = $statsResponse->json();
        $this->assertNotEmpty($stats);
        $providerStat = collect($stats)->firstWhere('provider_id', $this->provider->id);
        $this->assertNotNull($providerStat);
        $this->assertEquals(80.00, (float) $providerStat['total_amount']);
        $this->assertEquals(8.00, (float) $providerStat['total_commission']);
    }
}
