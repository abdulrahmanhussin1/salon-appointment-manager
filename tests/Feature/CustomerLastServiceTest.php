<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerLastServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;

    protected Branch $branch;

    protected Customer $customer;

    protected Employee $provider;

    protected PaymentMethod $paymentMethod;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'sales_invoices.index',
            'sales_invoices.create',
            'sales_invoices.show',
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

        $this->cashierUser = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->cashierUser->givePermissionTo($permissions);

        $this->branch = Branch::create([
            'name' => 'Main Salon',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '1234567890',
            'status' => 'active',
            'last_service' => null,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $employeeLevel = EmployeeLevel::create([
            'name' => 'Stylist Level',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Staff Provider',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'employee_level_id' => $employeeLevel->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Haircut',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->service = Service::create([
            'name' => 'Basic Haircut',
            'price' => 50.00,
            'price_can_change' => false,
            'duration' => 30,
            'status' => 'active',
            'service_category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);
    }

    private function buildInvoicePayload(string $status, string $invoiceDate): array
    {
        return [
            'customer_id' => $this->customer->id,
            'invoice_date' => $invoiceDate,
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => $status,
            'cash_payment' => 50.00,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $this->service->id,
                    'code' => 'SRV-001',
                    'price' => 50.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];
    }

    public function test_active_invoice_updates_customer_last_service(): void
    {
        $this->assertNull($this->customer->last_service);

        $payload = $this->buildInvoicePayload('active', '2026-01-15');

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        $this->assertEquals('2026-01-15', $this->customer->fresh()->last_service);
    }

    public function test_draft_invoice_does_not_update_customer_last_service(): void
    {
        $this->assertNull($this->customer->last_service);

        $payload = $this->buildInvoicePayload('draft', '2026-01-15');

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        $this->assertNull($this->customer->fresh()->last_service);
    }

    public function test_inactive_invoice_does_not_update_customer_last_service(): void
    {
        $this->assertNull($this->customer->last_service);

        $payload = $this->buildInvoicePayload('inactive', '2026-01-15');

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        $this->assertNull($this->customer->fresh()->last_service);
    }

    public function test_more_recent_active_invoice_updates_last_service(): void
    {
        $this->customer->update(['last_service' => '2026-01-15']);

        $payload = $this->buildInvoicePayload('active', '2026-02-20');

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        $this->assertEquals('2026-02-20', $this->customer->fresh()->last_service);
    }

    public function test_backdated_active_invoice_does_not_overwrite_more_recent_last_service(): void
    {
        $this->customer->update(['last_service' => '2026-02-20']);

        $payload = $this->buildInvoicePayload('active', '2026-01-10');

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Must remain 2026-02-20 (the most recent visit)
        $this->assertEquals('2026-02-20', $this->customer->fresh()->last_service);
    }

    public function test_retail_only_product_invoice_does_not_update_customer_last_service(): void
    {
        $category = \App\Models\ProductCategory::create([
            'name' => 'Retail Category',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);
        $unit = \App\Models\Unit::create([
            'name' => 'Piece',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);
        $product = \App\Models\Product::create([
            'name' => 'Styling Wax',
            'sku' => 'WAX-001',
            'price' => 30.00,
            'cost' => 15.00,
            'product_category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);
        $inventory = \App\Models\Inventory::create([
            'name' => 'Main Shelf',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);
        \Illuminate\Support\Facades\DB::table('inventory_products')->insert([
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Wax Supplier',
            'created_by' => $this->cashierUser->id,
        ]);
        $purchaseInvoice = \App\Models\PurchaseInvoice::create([
            'invoice_number' => 2001,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 300,
            'status' => 'active',
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);
        \App\Models\SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'supplier_price' => 15,
            'customer_price' => 30,
            'quantity' => 10,
        ]);

        $this->assertNull($this->customer->last_service);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-03-01',
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'cash_payment' => 30.00,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'product',
                    'item_id' => $product->id,
                    'code' => 'WAX-001',
                    'price' => 30.00,
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Under BR-P009, retail-only product purchases do NOT count as a service visit
        $this->assertNull($this->customer->fresh()->last_service);
    }
}
