<?php

namespace Tests\Feature;

use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\SupplierPrice;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for REQ-018: Manual Stock Adjustment Workflow (GAP-014).
 */
class ManualStockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierA;

    protected User $cashierB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected Employee $employeeA;

    protected Employee $employeeB;

    protected Inventory $inventoryA;

    protected Inventory $inventoryB;

    protected Product $product1;

    protected Product $product2;

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
            'inventories.index',
            'inventories.show',
            'inventories.create',
            'inventories.edit',
            'inventories.destroy',
            'inventory_transactions.transferView',
            'inventory_transactions.adjustView',
            'reports.index',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => explode('.', $perm)[0],
            ]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
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
            'name' => 'Staff Alpha',
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'created_by' => 1,
        ]);

        $this->employeeB = Employee::create([
            'name' => 'Staff Beta',
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'employee_level_id' => $level->id,
            'created_by' => 1,
        ]);

        $this->cashierA = User::create([
            'name' => 'Cashier Alpha',
            'email' => 'cashier_a_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeA->id,
            'created_by' => 1,
        ]);
        $this->cashierA->assignRole('cashier');

        $this->cashierB = User::create([
            'name' => 'Cashier Beta',
            'email' => 'cashier_b_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeB->id,
            'created_by' => 1,
        ]);
        $this->cashierB->assignRole('cashier');

        $this->inventoryA = Inventory::create([
            'name' => 'Alpha Stockroom',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->inventoryB = Inventory::create([
            'name' => 'Beta Stockroom',
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => 1,
        ]);

        $unit = Unit::create(['name' => 'Piece', 'status' => 'active', 'branch_id' => $this->branchA->id, 'created_by' => 1]);
        $cat = ProductCategory::create(['name' => 'Shampoo', 'status' => 'active', 'created_by' => 1]);
        $supplier = Supplier::create(['name' => 'Loreal', 'phone' => '010000000', 'status' => 'active', 'created_by' => 1]);

        $this->product1 = Product::create([
            'name' => 'Shampoo 500ml',
            'code' => 'SH-500',
            'unit_id' => $unit->id,
            'product_category_id' => $cat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->product2 = Product::create([
            'name' => 'Conditioner 500ml',
            'code' => 'CD-500',
            'unit_id' => $unit->id,
            'product_category_id' => $cat->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => 1,
        ]);

        $purchaseInvoice = \App\Models\PurchaseInvoice::create([
            'invoice_number' => 1001,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 1100,
            'status' => 'active',
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branchA->id,
            'created_by' => 1,
        ]);

        SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'product_id' => $this->product1->id,
            'supplier_id' => $supplier->id,
            'supplier_price' => 50,
            'customer_price' => 80,
            'quantity' => 100,
            'created_by' => 1,
        ]);

        SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'product_id' => $this->product2->id,
            'supplier_id' => $supplier->id,
            'supplier_price' => 60,
            'customer_price' => 90,
            'quantity' => 100,
            'created_by' => 1,
        ]);

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'block_insufficient_consumables' => false,
            'void_time_window_hours' => 24,
            'created_by' => 1,
        ]);
    }

    /**
     * Test schema has adjustment columns on inventory_transactions.
     */
    public function test_schema_has_adjustment_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'adjustment_type'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'adjustment_reason'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'notes'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'created_by'));
        $this->assertTrue(Schema::hasColumn('inventory_transactions', 'updated_by'));
    }

    /**
     * Test guest cannot access adjustment page or submit adjustment.
     */
    public function test_guest_cannot_access_or_submit_adjustments(): void
    {
        $this->get(route('inventory_transactions.adjustView'))
            ->assertRedirect(route('login'));

        $this->post(route('inventory_transactions.adjust'), [])
            ->assertRedirect(route('login'));
    }

    /**
     * Test authorized user can view adjustment page.
     */
    public function test_authorized_user_can_view_adjust_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory_transactions.adjustView'));

        $response->assertStatus(200);
        $response->assertSee(__('Manual Stock Adjustment'));
        $response->assertSee($this->inventoryA->name);
        $response->assertSee($this->product1->name);
    }

    /**
     * Test increasing stock (+) increments inventory quantity and records transaction.
     */
    public function test_increase_stock_increments_quantity_and_creates_records(): void
    {
        // Initially set stock to 10
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 10,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'increase',
            'adjustment_reason' => 'count_correction',
            'notes' => 'Found 5 extra bottles in storage audit',
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertRedirect(route('inventory_transactions.history'));

        // Check stock updated to 15
        $invProduct = InventoryProduct::where('inventory_id', $this->inventoryA->id)
            ->where('product_id', $this->product1->id)
            ->first();
        $this->assertEquals(15, $invProduct->quantity);

        // Check transaction record
        $tx = InventoryTransaction::latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals('adjustment', $tx->transaction_type);
        $this->assertEquals('increase', $tx->adjustment_type);
        $this->assertEquals('count_correction', $tx->adjustment_reason);
        $this->assertEquals($this->inventoryA->id, $tx->destination_inventory_id);
        $this->assertNull($tx->source_inventory_id);
        $this->assertEquals($this->adminUser->id, $tx->created_by);
        $this->assertEquals('Found 5 extra bottles in storage audit', $tx->notes);

        // Check details
        $this->assertCount(1, $tx->transactionDetails);
        $this->assertEquals($this->product1->id, $tx->transactionDetails->first()->product_id);
        $this->assertEquals(5, $tx->transactionDetails->first()->quantity);
    }

    /**
     * Test increasing stock creates new inventory_product record if not exists.
     */
    public function test_increase_stock_creates_new_row_if_product_not_in_inventory(): void
    {
        $this->assertDatabaseMissing('inventory_products', [
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product2->id,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'increase',
            'adjustment_reason' => 'count_correction',
            'products' => [
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertRedirect(route('inventory_transactions.history'));

        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product2->id,
            'quantity' => 20,
        ]);
    }

    /**
     * Test decreasing stock (-) decrements inventory quantity and records transaction.
     */
    public function test_decrease_stock_decrements_quantity_and_creates_records(): void
    {
        // Initially set stock to 15
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 15,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'decrease',
            'adjustment_reason' => 'damage',
            'notes' => 'Bottle broke during cleaning',
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 3,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertRedirect(route('inventory_transactions.history'));

        // Check stock decremented to 12
        $invProduct = InventoryProduct::where('inventory_id', $this->inventoryA->id)
            ->where('product_id', $this->product1->id)
            ->first();
        $this->assertEquals(12, $invProduct->quantity);

        // Check transaction record
        $tx = InventoryTransaction::latest('id')->first();
        $this->assertEquals('adjustment', $tx->transaction_type);
        $this->assertEquals('decrease', $tx->adjustment_type);
        $this->assertEquals('damage', $tx->adjustment_reason);
        $this->assertEquals($this->inventoryA->id, $tx->source_inventory_id);
        $this->assertNull($tx->destination_inventory_id);
    }

    /**
     * Test decreasing stock beyond available quantity fails with error and does not mutate stock.
     */
    public function test_decrease_stock_beyond_available_is_blocked(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 4,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'decrease',
            'adjustment_reason' => 'theft',
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 10, // Exceeds available 4!
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        // Stock must remain unchanged at 4
        $invProduct = InventoryProduct::where('inventory_id', $this->inventoryA->id)
            ->where('product_id', $this->product1->id)
            ->first();
        $this->assertEquals(4, $invProduct->quantity);

        // No adjustment transaction should be created
        $this->assertEquals(0, InventoryTransaction::where('transaction_type', 'adjustment')->count());
    }

    /**
     * Test reason 'other' requires notes.
     */
    public function test_reason_other_requires_notes(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 10,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'decrease',
            'adjustment_reason' => 'other',
            'notes' => '', // Missing!
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertSessionHasErrors(['notes']);
    }

    /**
     * Test adjusting multiple products in single transaction.
     */
    public function test_multiple_products_adjustment_in_single_transaction(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 20,
        ]);

        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product2->id,
            'quantity' => 30,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'decrease',
            'adjustment_reason' => 'waste',
            'notes' => 'Batch expiry cleanup',
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 5,
                ],
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertRedirect(route('inventory_transactions.history'));

        $this->assertEquals(15, InventoryProduct::where('inventory_id', $this->inventoryA->id)->where('product_id', $this->product1->id)->value('quantity'));
        $this->assertEquals(20, InventoryProduct::where('inventory_id', $this->inventoryA->id)->where('product_id', $this->product2->id)->value('quantity'));

        $tx = InventoryTransaction::latest('id')->first();
        $this->assertCount(2, $tx->transactionDetails);
    }

    /**
     * Test Cashier A cannot adjust stock for an inventory belonging to Branch B (server-side branch isolation).
     */
    public function test_cashier_cannot_adjust_stock_for_another_branch(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryB->id,
            'product_id' => $this->product1->id,
            'quantity' => 20,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryB->id, // Belongs to Branch B! Cashier A is in Branch A
            'adjustment_type' => 'increase',
            'adjustment_reason' => 'count_correction',
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA)
            ->post(route('inventory_transactions.adjust'), $payload);

        $response->assertStatus(403);

        // Stock in Branch B must remain unchanged
        $this->assertEquals(20, InventoryProduct::where('inventory_id', $this->inventoryB->id)->where('product_id', $this->product1->id)->value('quantity'));
    }

    /**
     * Test checkStock API returns current quantity and respects branch scoping.
     */
    public function test_check_stock_api_returns_quantity_and_enforces_branch_scoping(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 42,
        ]);

        // Authorized own-branch query
        $res = $this->actingAs($this->cashierA)
            ->getJson(route('inventory_transactions.check_stock', [
                'inventory_id' => $this->inventoryA->id,
                'product_id' => $this->product1->id,
            ]));

        $res->assertStatus(200);
        $res->assertJson(['quantity' => 42]);

        // Cross-branch query is blocked with 403
        $forbiddenRes = $this->actingAs($this->cashierA)
            ->getJson(route('inventory_transactions.check_stock', [
                'inventory_id' => $this->inventoryB->id,
                'product_id' => $this->product1->id,
            ]));

        $forbiddenRes->assertStatus(403);
    }

    /**
     * Test Adjustment History page renders and DataTables endpoint returns correct records.
     */
    public function test_adjustment_history_page_and_datatables_endpoint(): void
    {
        // Create an increase adjustment
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 10,
        ]);

        $postRes = $this->actingAs($this->adminUser)->post(route('inventory_transactions.adjust'), [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'increase',
            'adjustment_reason' => 'count_correction',
            'notes' => 'Audit check',
            'products' => [
                ['product_id' => $this->product1->id, 'quantity' => 5],
            ],
        ]);
        $postRes->assertRedirect(route('inventory_transactions.history'));

        // View history HTML page
        $pageResponse = $this->actingAs($this->adminUser)
            ->get(route('inventory_transactions.history'));

        $pageResponse->assertStatus(200);
        $pageResponse->assertSee(__('Stock Adjustment Audit History'));

        // Query DataTables JSON endpoint
        $dataResponse = $this->actingAs($this->adminUser)
            ->getJson(route('inventory_transactions.history_data'));

        $dataResponse->assertStatus(200);
        $data = $dataResponse->json();

        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertEquals($this->inventoryA->name, $data['data'][0]['inventory_name']);
        $this->assertStringContainsString('Increase (+)', $data['data'][0]['type_badge']);
        $this->assertStringContainsString('Audit check', $data['data'][0]['notes']);
    }

    /**
     * Test Store Balance Report factors manual adjustments into in_qty and out_qty.
     */
    public function test_store_balance_report_includes_adjustments(): void
    {
        // 1. Initial product with stock
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product1->id,
            'quantity' => 10,
        ]);

        // 2. Perform Increase Adjustment of 8
        $this->actingAs($this->adminUser)->post(route('inventory_transactions.adjust'), [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'increase',
            'adjustment_reason' => 'count_correction',
            'products' => [
                ['product_id' => $this->product1->id, 'quantity' => 8],
            ],
        ]);

        // 3. Perform Decrease Adjustment of 3
        $this->actingAs($this->adminUser)->post(route('inventory_transactions.adjust'), [
            'date' => now()->toDateString(),
            'inventory_id' => $this->inventoryA->id,
            'adjustment_type' => 'decrease',
            'adjustment_reason' => 'damage',
            'products' => [
                ['product_id' => $this->product1->id, 'quantity' => 3],
            ],
        ]);

        // Query store balance report data
        $reportRes = $this->actingAs($this->adminUser)
            ->getJson(route('report.stock_balance_transfer', [
                'inventory_id' => $this->inventoryA->id,
            ]));

        $reportRes->assertStatus(200);
        $reportData = $reportRes->json('data');

        $row = collect($reportData)->firstWhere('id', $this->product1->id);
        $this->assertNotNull($row);

        // In Qty should be 8 (from increase adjustment)
        $this->assertEquals(8, $row['in_qty']);

        // Out Qty should be 3 (from decrease adjustment)
        $this->assertEquals(3, $row['out_qty']);

        // On-hand Qty should be 10 + 8 - 3 = 15
        $this->assertEquals(15, $row['onhand_qty']);
    }
}
