<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\SupplierPrice;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DraftInvoiceInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;

    protected Branch $branch;

    protected Inventory $inventory;

    protected Customer $customer;

    protected Employee $provider;

    protected PaymentMethod $paymentMethod;

    protected Product $product;

    protected Supplier $supplier;

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

        $this->inventory = Inventory::create([
            'name' => 'Main Store',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Bob Customer',
            'phone' => '1234567890',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $employeeLevel = EmployeeLevel::create([
            'name' => 'Staff Level',
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

        $category = ProductCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $unit = Unit::create([
            'name' => 'Bottle',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $this->product = Product::create([
            'name' => 'Premium Shampoo',
            'code' => 'SHP-PREM',
            'price' => 150,
            'cost' => 80,
            'product_category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Supplier Co',
            'created_by' => $this->cashierUser->id,
        ]);

        $purchaseInvoice = \App\Models\PurchaseInvoice::create([
            'invoice_number' => 1001,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 1600,
            'status' => 'active',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'supplier_id' => $this->supplier->id,
            'product_id' => $this->product->id,
            'supplier_price' => 80,
            'customer_price' => 150,
            'quantity' => 20,
        ]);
    }

    private function setProductStock(int $quantity): void
    {
        DB::table('inventory_products')->updateOrInsert(
            [
                'inventory_id' => $this->inventory->id,
                'product_id' => $this->product->id,
            ],
            [
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function buildInvoicePayload(string $status, int $quantity = 3): array
    {
        return [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => $status,
            'cash_payment' => 150 * $quantity,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'product',
                    'item_id' => $this->product->id,
                    'code' => $this->product->code,
                    'price' => 150,
                    'provider_id' => $this->provider->id,
                    'quantity' => $quantity,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];
    }

    public function test_creating_draft_invoice_does_not_deduct_inventory(): void
    {
        $this->setProductStock(10);

        $payload = $this->buildInvoicePayload('draft', 3);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');
        $this->assertNotNull($invoiceId);

        $invoice = SalesInvoice::findOrFail($invoiceId);
        $this->assertEquals('draft', $invoice->status);

        // Inventory must remain exactly 10
        $currentStock = DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(10, $currentStock, 'Draft invoice must not decrement inventory');

        // No inventory transaction should be logged
        $this->assertEquals(0, InventoryTransaction::where('transaction_type', 'sales')->count());
    }

    public function test_creating_active_invoice_deducts_inventory(): void
    {
        $this->setProductStock(10);

        $payload = $this->buildInvoicePayload('active', 3);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);

        // Inventory must be decremented: 10 - 3 = 7
        $currentStock = DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(7, $currentStock);

        // Sales inventory transaction must be created
        $this->assertEquals(1, InventoryTransaction::where('transaction_type', 'sales')->count());
    }

    public function test_activating_draft_invoice_deducts_inventory_and_updates_status(): void
    {
        $this->setProductStock(10);

        // Create draft invoice with 4 units
        $payload = $this->buildInvoicePayload('draft', 4);
        $createResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);

        $invoiceId = $createResponse->json('invoice_id');
        $invoice = SalesInvoice::findOrFail($invoiceId);
        $this->assertEquals('draft', $invoice->status);

        // Verify stock is still 10 before activation
        $this->assertEquals(10, DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity'));

        // Activate the draft invoice via POST /sales_invoices/{id}/activate
        $activateResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.activate', $invoice->id));

        $activateResponse->assertStatus(200);

        // Invoice status must now be active
        $this->assertEquals('active', $invoice->fresh()->status);

        // Inventory must now be decremented: 10 - 4 = 6
        $currentStock = DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(6, $currentStock);

        // Sales inventory transaction must exist
        $this->assertEquals(1, InventoryTransaction::where('transaction_type', 'sales')->count());
    }

    public function test_activating_draft_invoice_fails_when_stock_is_insufficient(): void
    {
        $this->setProductStock(5);

        // Create draft for 5 units
        $payload = $this->buildInvoicePayload('draft', 5);
        $createResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);
        $invoiceId = $createResponse->json('invoice_id');

        // Now simulate stock decreasing to 2 before activation
        $this->setProductStock(2);

        // Attempt to activate draft
        $activateResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.activate', $invoiceId));

        $activateResponse->assertStatus(422);

        // Invoice remains draft
        $invoice = SalesInvoice::findOrFail($invoiceId);
        $this->assertEquals('draft', $invoice->status);

        // Stock remains untouched at 2
        $this->assertEquals(2, DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity'));
    }

    public function test_discarding_draft_invoice_has_zero_inventory_impact(): void
    {
        $this->setProductStock(10);

        $payload = $this->buildInvoicePayload('draft', 3);
        $createResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);
        $invoiceId = $createResponse->json('invoice_id');

        // Delete draft invoice
        $deleteResponse = $this->actingAs($this->cashierUser)
            ->deleteJson(route('sales_invoices.destroy', $invoiceId));

        $deleteResponse->assertStatus(200);

        // Invoice should be deleted
        $this->assertDatabaseMissing('sales_invoices', ['id' => $invoiceId]);

        // Stock must still be 10
        $currentStock = DB::table('inventory_products')
            ->where('inventory_id', $this->inventory->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(10, $currentStock);
    }

    public function test_cannot_delete_active_invoice(): void
    {
        $this->setProductStock(10);

        $payload = $this->buildInvoicePayload('active', 3);
        $createResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);
        $invoiceId = $createResponse->json('invoice_id');

        // Attempt to delete active invoice
        $deleteResponse = $this->actingAs($this->cashierUser)
            ->deleteJson(route('sales_invoices.destroy', $invoiceId));

        $deleteResponse->assertStatus(403);

        // Active invoice must still exist
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'active']);
    }

    public function test_deleting_draft_invoice_restores_customer_deposit(): void
    {
        $this->setProductStock(10);

        // Create initial available customer deposit of 50
        $deposit = \App\Models\CustomerTransaction::create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'reference_id' => 0,
            'amount' => 50.00,
            'reference_type' => 'deposit',
            'status' => 'available',
            'created_by' => $this->cashierUser->id,
        ]);

        // Create a draft invoice for 1 unit ($40.00) using $40.00 deposit
        $payload = $this->buildInvoicePayload('draft', 1);
        $payload['deposit'] = 40.00;
        $payload['cash_payment'] = 0;

        $createResponse = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $createResponse->assertStatus(200);
        $invoiceId = $createResponse->json('invoice_id');

        // Deposit was partially consumed: 50 - 40 = 10
        $deposit->refresh();
        $this->assertEquals(10.00, (float) $deposit->amount);

        // Usage record exists
        $this->assertDatabaseHas('customer_transactions', [
            'reference_type' => 'invoice',
            'reference_id' => $invoiceId,
            'amount' => -40.00,
        ]);

        // Discard / delete the draft invoice
        $deleteResponse = $this->actingAs($this->cashierUser)
            ->deleteJson(route('sales_invoices.destroy', $invoiceId));
        $deleteResponse->assertStatus(200);

        // Deposit must be restored to 50.00 and available
        $deposit->refresh();
        $this->assertEquals(50.00, (float) $deposit->amount);
        $this->assertEquals('available', $deposit->status);

        // Usage record must be cleaned up
        $this->assertDatabaseMissing('customer_transactions', [
            'reference_type' => 'invoice',
            'reference_id' => $invoiceId,
        ]);
    }

    public function test_sales_invoice_cannot_deduct_inventory_from_different_branch(): void
    {
        // Create Branch B with its own inventory
        $branchB = Branch::create([
            'name' => 'Branch B',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);
        $inventoryB = Inventory::create([
            'name' => 'Branch B Warehouse',
            'branch_id' => $branchB->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        // Put 10 units in Branch B's inventory, but 0 in Branch A's inventory
        $this->setProductStock(0);
        DB::table('inventory_products')->insert([
            'inventory_id' => $inventoryB->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cashier at Branch A attempts to create active invoice for 2 units
        $payload = $this->buildInvoicePayload('active', 2);
        $payload['branch_id'] = $this->branch->id;

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        // Must fail with 422 because Branch A has 0 stock, even though Branch B has 10
        $response->assertStatus(422);

        // Branch B stock must remain untouched at 10
        $this->assertEquals(10, DB::table('inventory_products')
            ->where('inventory_id', $inventoryB->id)
            ->where('product_id', $this->product->id)
            ->value('quantity'));
    }
}
