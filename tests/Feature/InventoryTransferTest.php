<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Branch $branch;

    protected Inventory $sourceInventory;

    protected Inventory $destInventory;

    protected Product $productA;

    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'inventory_transactions.transferView',
            'inventory_transactions.transfer',
            'inventories.index',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => 'inventory_transactions',
            ]);
        }

        \Illuminate\Support\Facades\View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
        ]);

        $this->adminUser = User::create([
            'name' => 'Inventory Admin',
            'email' => 'inv_admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->adminUser->givePermissionTo($permissions);

        $this->branch = Branch::create([
            'name' => 'Central Branch',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->sourceInventory = Inventory::create([
            'name' => 'Warehouse Source',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->destInventory = Inventory::create([
            'name' => 'Branch Destination',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $category = ProductCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $unit = Unit::create([
            'name' => 'Bottle',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->productA = Product::create([
            'name' => 'Shampoo 500ml',
            'sku' => 'SHP-001',
            'price' => 100,
            'cost' => 60,
            'product_category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->productB = Product::create([
            'name' => 'Conditioner 500ml',
            'sku' => 'CND-001',
            'price' => 120,
            'cost' => 70,
            'product_category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_transfer_to_destination_without_existing_inventory_product_creates_row(): void
    {
        // Source has 15 units; Destination has NO record at all
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseMissing('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
        ]);

        $payload = [
            'invoice_date' => now()->toDateString(),
            'source_inventory' => $this->sourceInventory->id,
            'destination_inventory' => $this->destInventory->id,
            'products' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 7,
                    'unit_price' => 60,
                    'total' => 420,
                ],
            ],
            'total_before_discount' => 420,
            'discount' => 0,
            'delivery_expense' => 0,
            'other_expenses' => 0,
            'added_value_tax' => 0,
            'commercial_tax' => 0,
            'net_total' => 420,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.transfer'), $payload);

        $response->assertSessionHasNoErrors();

        // Source must be decremented: 15 - 7 = 8
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 8,
        ]);

        // Destination must now exist with quantity: 7
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 7,
        ]);

        // Transaction record must exist
        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => 'transfer',
            'source_inventory_id' => $this->sourceInventory->id,
            'destination_inventory_id' => $this->destInventory->id,
            'net_total' => 420,
        ]);

        // Detail record must exist
        $this->assertDatabaseHas('inventory_transaction_details', [
            'product_id' => $this->productA->id,
            'quantity' => 7,
        ]);
    }

    public function test_transfer_to_destination_with_existing_inventory_product_increments_correctly(): void
    {
        // Source has 20; Destination already has 5
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_products')->insert([
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'invoice_date' => now()->toDateString(),
            'source_inventory' => $this->sourceInventory->id,
            'destination_inventory' => $this->destInventory->id,
            'products' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10,
                    'unit_price' => 60,
                    'total' => 600,
                ],
            ],
            'total_before_discount' => 600,
            'discount' => 0,
            'delivery_expense' => 0,
            'other_expenses' => 0,
            'added_value_tax' => 0,
            'commercial_tax' => 0,
            'net_total' => 600,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.transfer'), $payload);

        $response->assertSessionHasNoErrors();

        // Source decremented: 20 - 10 = 10
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        // Destination incremented: 5 + 10 = 15
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 15,
        ]);
    }

    public function test_transfer_fails_when_source_stock_is_insufficient(): void
    {
        // Source only has 3 units
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'invoice_date' => now()->toDateString(),
            'source_inventory' => $this->sourceInventory->id,
            'destination_inventory' => $this->destInventory->id,
            'products' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5, // Requests 5 when only 3 available
                    'unit_price' => 60,
                    'total' => 300,
                ],
            ],
            'total_before_discount' => 300,
            'net_total' => 300,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.transfer'), $payload);

        // Must redirect to transferView
        $response->assertRedirect(route('inventory_transactions.transferView'));

        // Stock in source must remain untouched (3)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 3,
        ]);

        // No destination row created
        $this->assertDatabaseMissing('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
        ]);

        // No transaction created
        $this->assertEquals(0, InventoryTransaction::count());
    }

    public function test_transfer_with_multiple_products(): void
    {
        // Product A: 10 units in source, none in dest
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Product B: 20 units in source, 3 in dest
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productB->id,
            'quantity' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_products')->insert([
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productB->id,
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'invoice_date' => now()->toDateString(),
            'source_inventory' => $this->sourceInventory->id,
            'destination_inventory' => $this->destInventory->id,
            'products' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 4,
                    'unit_price' => 60,
                    'total' => 240,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 8,
                    'unit_price' => 70,
                    'total' => 560,
                ],
            ],
            'total_before_discount' => 800,
            'net_total' => 800,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.transfer'), $payload);

        $response->assertSessionHasNoErrors();

        // Product A: source 10 - 4 = 6; dest 0 + 4 = 4
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 6,
        ]);
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 4,
        ]);

        // Product B: source 20 - 8 = 12; dest 3 + 8 = 11
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productB->id,
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->destInventory->id,
            'product_id' => $this->productB->id,
            'quantity' => 11,
        ]);

        // Two details created
        $this->assertEquals(2, InventoryTransactionDetail::count());
    }

    public function test_transfer_with_aggregated_duplicate_products_fails_when_total_exceeds_stock(): void
    {
        // Source only has 10 units
        DB::table('inventory_products')->insert([
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Two lines for productA: 6 and 5. Total = 11 > 10.
        $payload = [
            'invoice_date' => now()->toDateString(),
            'source_inventory' => $this->sourceInventory->id,
            'destination_inventory' => $this->destInventory->id,
            'products' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 6,
                    'unit_price' => 60,
                    'total' => 360,
                ],
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                    'unit_price' => 60,
                    'total' => 300,
                ],
            ],
            'total_before_discount' => 660,
            'net_total' => 660,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory_transactions.transfer'), $payload);

        $response->assertRedirect(route('inventory_transactions.transferView'));

        // Stock in source must remain untouched (10)
        $this->assertDatabaseHas('inventory_products', [
            'inventory_id' => $this->sourceInventory->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $this->assertEquals(0, InventoryTransaction::count());
    }
}
