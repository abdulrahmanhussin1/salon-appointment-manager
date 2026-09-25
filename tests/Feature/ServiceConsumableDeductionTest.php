<?php

namespace Tests\Feature;

use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceProduct;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceConsumableDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;

    protected Branch $branch;

    protected Branch $otherBranch;

    protected Inventory $inventory;

    protected Inventory $otherInventory;

    protected Customer $customer;

    protected Employee $provider;

    protected PaymentMethod $paymentMethod;

    protected ServiceCategory $serviceCategory;

    protected ProductCategory $productCategory;

    protected Unit $unit;

    protected Product $consumableA;

    protected Product $consumableB;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->cashierUser = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->cashierUser->givePermissionTo($permissions);

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'block_insufficient_consumables' => false,
            'created_by' => $this->cashierUser->id,
        ]);

        \Illuminate\Support\Facades\View::share('adminPanelSetting', AdminPanelSetting::first());

        $this->branch = Branch::create([
            'name' => 'Main Salon',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->otherBranch = Branch::create([
            'name' => 'Downtown Salon',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->inventory = Inventory::create([
            'name' => 'Main Store',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->otherInventory = Inventory::create([
            'name' => 'Downtown Store',
            'branch_id' => $this->otherBranch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '1234567890',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $employeeLevel = EmployeeLevel::create([
            'name' => 'Stylist Level',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Jane Stylist',
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

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Hair Treatments',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->productCategory = ProductCategory::create([
            'name' => 'Salon Consumables',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->unit = Unit::create([
            'name' => 'ml',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $this->consumableA = Product::create([
            'name' => 'Color Cream Red',
            'code' => 'COL-RED',
            'price' => 50,
            'cost' => 20,
            'type' => 'operation',
            'product_category_id' => $this->productCategory->id,
            'unit_id' => $this->unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->consumableB = Product::create([
            'name' => 'Developer 20 Vol',
            'code' => 'DEV-20V',
            'price' => 30,
            'cost' => 10,
            'type' => 'operation',
            'product_category_id' => $this->productCategory->id,
            'unit_id' => $this->unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->service = Service::create([
            'name' => 'Hair Coloring',
            'description' => 'Professional Hair Coloring',
            'duration' => '60',
            'price' => 200,
            'price_can_change' => false,
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);
    }

    private function setProductStock(Product $product, Inventory $inventory, int $quantity): void
    {
        DB::table('inventory_products')->updateOrInsert(
            [
                'inventory_id' => $inventory->id,
                'product_id' => $product->id,
            ],
            [
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function attachConsumable(Service $service, Product $product, int $quantity): void
    {
        ServiceProduct::create([
            'service_id' => $service->id,
            'product_id' => $product->id,
            'product_quantity' => $quantity,
        ]);
    }

    private function buildServiceInvoicePayload(string $status, int $serviceQuantity = 1, ?int $branchId = null): array
    {
        $effectiveBranchId = $branchId ?? $this->branch->id;

        return [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $effectiveBranchId,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => $status,
            'cash_payment' => $this->service->price * $serviceQuantity,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $this->service->id,
                    'code' => 'SRV-'.$this->service->id,
                    'price' => $this->service->price,
                    'provider_id' => $this->provider->id,
                    'quantity' => $serviceQuantity,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];
    }

    public function test_selling_service_with_consumable_deducts_inventory_and_records_transaction(): void
    {
        // Service uses 2 units of Consumable A per service
        $this->attachConsumable($this->service, $this->consumableA, 2);

        // Branch inventory has 10 units of Consumable A
        $this->setProductStock($this->consumableA, $this->inventory, 10);

        // Sell 3 units of the service -> should deduct 2 * 3 = 6 units
        $payload = $this->buildServiceInvoicePayload('active', 3);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Assert stock decremented by 6 (10 - 6 = 4)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 4,
        ]);

        // Assert InventoryTransaction created with type 'service_consumption'
        $transaction = InventoryTransaction::where('transaction_type', 'service_consumption')->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($this->inventory->id, $transaction->source_inventory_id);

        // Assert InventoryTransactionDetail created with quantity 6
        $detail = InventoryTransactionDetail::where('inventory_transaction_id', $transaction->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals($this->consumableA->id, $detail->product_id);
        $this->assertEquals(6, $detail->quantity);
    }

    public function test_draft_invoice_with_service_does_not_deduct_consumables_until_activated(): void
    {
        $this->attachConsumable($this->service, $this->consumableA, 2);
        $this->setProductStock($this->consumableA, $this->inventory, 10);

        // 1. Create draft invoice
        $payload = $this->buildServiceInvoicePayload('draft', 2);
        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        // Stock must still be 10 (untouched)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 10,
        ]);

        // No service_consumption transaction created
        $this->assertDatabaseMissing('inventory_transactions', [
            'transaction_type' => 'service_consumption',
        ]);

        // 2. Activate draft invoice
        $invoice = SalesInvoice::findOrFail($invoiceId);
        $activateResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.activate', $invoice));

        $activateResponse->assertStatus(200);

        // Stock must now be decremented by 2 * 2 = 4 (10 - 4 = 6)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 6,
        ]);

        // Transaction recorded
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'service_consumption',
        ]);
        $this->assertDatabaseHas('inventory_transaction_details', [
            'product_id' => $this->consumableA->id,
            'quantity' => 4,
        ]);
    }

    public function test_insufficient_consumable_stock_generates_warning_and_allows_sale_by_default(): void
    {
        // Default: block_insufficient_consumables is false
        $this->attachConsumable($this->service, $this->consumableA, 10);
        $this->setProductStock($this->consumableA, $this->inventory, 4);

        $payload = $this->buildServiceInvoicePayload('active', 1);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice_id', 'warnings']);
        $warnings = $response->json('warnings');
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Insufficient stock for consumable', $warnings[0]);

        // Inventory should go negative (4 - 10 = -6) per DEC-005
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => -6,
        ]);

        // Transaction of type service_consumption recorded
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'service_consumption',
        ]);
        $this->assertDatabaseHas('inventory_transaction_details', [
            'product_id' => $this->consumableA->id,
            'quantity' => 10,
        ]);
    }

    public function test_insufficient_consumable_stock_blocks_sale_when_setting_is_enabled(): void
    {
        // Enable block on insufficient consumables
        AdminPanelSetting::first()->update(['block_insufficient_consumables' => true]);

        $this->attachConsumable($this->service, $this->consumableA, 10);
        $this->setProductStock($this->consumableA, $this->inventory, 4);

        $payload = $this->buildServiceInvoicePayload('active', 1);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'error' => 'Insufficient inventory for consumable Color Cream Red',
        ]);

        // Inventory must remain untouched (4)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 4,
        ]);

        // No transactions or invoice created
        $this->assertDatabaseMissing('inventory_transactions', [
            'transaction_type' => 'service_consumption',
        ]);
        $this->assertDatabaseCount('sales_invoices', 0);
    }

    public function test_insufficient_consumable_stock_blocks_draft_activation_when_setting_is_enabled(): void
    {
        // Enable block on insufficient consumables
        AdminPanelSetting::first()->update(['block_insufficient_consumables' => true]);

        $this->attachConsumable($this->service, $this->consumableA, 10);
        $this->setProductStock($this->consumableA, $this->inventory, 4);

        // Draft invoice creation succeeds (does not deduct inventory)
        $payload = $this->buildServiceInvoicePayload('draft', 1);
        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        // Activation must fail due to insufficient consumable stock
        $invoice = SalesInvoice::findOrFail($invoiceId);
        $activateResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.activate', $invoice));

        $activateResponse->assertStatus(422);
        $activateResponse->assertJsonFragment([
            'error' => 'Insufficient inventory for consumable Color Cream Red',
        ]);

        // Invoice remains draft
        $invoice->refresh();
        $this->assertEquals('draft', $invoice->status);

        // Stock remains untouched
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 4,
        ]);
    }

    public function test_consumable_deduction_is_isolated_to_invoice_branch(): void
    {
        $this->attachConsumable($this->service, $this->consumableA, 3);

        // Other branch has 20 units; Main branch has 5 units
        $this->setProductStock($this->consumableA, $this->otherInventory, 20);
        $this->setProductStock($this->consumableA, $this->inventory, 5);

        // Sale at Main branch for 1 service (deducts 3)
        $payload = $this->buildServiceInvoicePayload('active', 1, $this->branch->id);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Main branch inventory reduced from 5 to 2
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 2,
        ]);

        // Other branch inventory completely untouched (still 20)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->otherInventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 20,
        ]);
    }

    public function test_service_with_multiple_consumables_deducts_all(): void
    {
        // Service uses 2 of Consumable A and 5 of Consumable B
        $this->attachConsumable($this->service, $this->consumableA, 2);
        $this->attachConsumable($this->service, $this->consumableB, 5);

        $this->setProductStock($this->consumableA, $this->inventory, 20);
        $this->setProductStock($this->consumableB, $this->inventory, 30);

        // Sell 2 units of the service -> Consumable A: 2*2=4, Consumable B: 5*2=10
        $payload = $this->buildServiceInvoicePayload('active', 2);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Consumable A: 20 - 4 = 16
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableA->id,
            'quantity' => 16,
        ]);

        // Consumable B: 30 - 10 = 20
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableB->id,
            'quantity' => 20,
        ]);

        $this->assertDatabaseHas('inventory_transaction_details', [
            'product_id' => $this->consumableA->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('inventory_transaction_details', [
            'product_id' => $this->consumableB->id,
            'quantity' => 10,
        ]);
    }

    public function test_service_without_consumables_creates_invoice_without_inventory_transactions(): void
    {
        // Service has no consumables linked in service_products
        $payload = $this->buildServiceInvoicePayload('active', 1);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('inventory_transactions', [
            'transaction_type' => 'service_consumption',
        ]);
    }
}
