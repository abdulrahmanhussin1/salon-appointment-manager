<?php

namespace Tests\Feature;

use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Refund;
use App\Models\RefundDetail;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RefundWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierUserA;

    protected User $cashierUserB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected Inventory $inventoryA;

    protected Inventory $inventoryB;

    protected Customer $customer;

    protected PaymentMethod $cashMethod;

    protected Product $product;

    protected Service $service;

    protected Employee $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'sales_invoices.index',
            'sales_invoices.create',
            'sales_invoices.show',
            'sales_invoices.void',
            'refunds.index',
            'refunds.create',
            'refunds.show',
            'report.daily_revenues',
            'reports.index',
            'inventories.index',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
                'group' => explode('.', $perm)[0],
            ]);
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->adminUser->givePermissionTo($permissions);
        $this->adminUser->assignRole('admin');

        $this->branchA = Branch::create([
            'name' => 'Branch Downtown',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Branch Uptown',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->inventoryA = Inventory::create([
            'name' => 'Downtown Stock',
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->inventoryB = Inventory::create([
            'name' => 'Uptown Stock',
            'branch_id' => $this->branchB->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Stylist Level',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $employeeA = Employee::create([
            'name' => 'Cashier Employee A',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $employeeB = Employee::create([
            'name' => 'Cashier Employee B',
            'branch_id' => $this->branchB->id,
            'employee_level_id' => $level->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Senior Provider',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashierUserA = User::create([
            'name' => 'Cashier A',
            'email' => 'cashier_a_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $employeeA->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierUserA->givePermissionTo($permissions);
        $this->cashierUserA->assignRole('cashier');

        $this->cashierUserB = User::create([
            'name' => 'Cashier B',
            'email' => 'cashier_b_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $employeeB->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierUserB->givePermissionTo($permissions);
        $this->cashierUserB->assignRole('cashier');

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'void_time_window_hours' => 24,
            'created_by' => $this->adminUser->id,
        ]);
        \Illuminate\Support\Facades\View::share('adminPanelSetting', AdminPanelSetting::first());

        $this->cashMethod = PaymentMethod::create([
            'name' => 'cash',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Alice Customer',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $pCategory = ProductCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $unit = Unit::create([
            'name' => 'Bottle',
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->product = Product::create([
            'name' => 'Luxury Shampoo',
            'code' => 'SHP-001',
            'product_category_id' => $pCategory->id,
            'unit_id' => $unit->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Loreal',
            'phone' => '010000000',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $purchaseInvoice = \App\Models\PurchaseInvoice::create([
            'invoice_number' => 1001,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100,
            'status' => 'active',
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\SupplierPrice::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'supplier_price' => 20,
            'customer_price' => 30,
            'quantity' => 100,
            'created_by' => $this->adminUser->id,
        ]);

        $sCategory = ServiceCategory::create([
            'name' => 'Styling',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->service = Service::create([
            'name' => 'Haircut & Styling',
            'service_category_id' => $sCategory->id,
            'branch_id' => $this->branchA->id,
            'price' => 50.00,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    /**
     * Test schema changes for refunds and returns.
     */
    public function test_schema_has_refund_columns_and_tables(): void
    {
        $this->assertTrue(Schema::hasTable('refunds'));
        $this->assertTrue(Schema::hasTable('refund_details'));

        $this->assertTrue(Schema::hasColumns('refunds', [
            'id', 'refund_number', 'sales_invoice_id', 'customer_id', 'branch_id',
            'refund_date', 'refund_method', 'total_refund_amount', 'tax_refund_amount',
            'commission_reversed_amount', 'reason', 'notes', 'created_by',
        ]));

        $this->assertTrue(Schema::hasColumns('refund_details', [
            'id', 'refund_id', 'sales_invoice_detail_id', 'service_id', 'product_id',
            'provider_id', 'quantity', 'unit_price', 'discount', 'subtotal',
            'commission_reversed', 'inventory_restored', 'inventory_id', 'created_by',
        ]));

        $this->assertTrue(Schema::hasColumns('sales_invoices', [
            'refund_status', 'total_refunded',
        ]));

        $this->assertTrue(Schema::hasColumns('sales_invoice_details', [
            'refunded_quantity', 'refunded_amount',
        ]));
    }

    /**
     * Test unauthenticated guests cannot access or submit refunds.
     */
    public function test_guest_cannot_access_or_submit_refunds(): void
    {
        $this->get(route('refunds.index'))->assertRedirect(route('login'));
        $this->get(route('refunds.create'))->assertRedirect(route('login'));
        $this->post(route('refunds.store'), [])->assertRedirect(route('login'));
    }

    /**
     * Test draft and voided invoices cannot be refunded.
     */
    public function test_cannot_refund_draft_or_voided_invoice(): void
    {
        // 1. Draft invoice
        $draftInvoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100,
            'net_total' => 100,
            'paid_amount_cash' => 100,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'draft',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $draftInvoice->id,
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 50,
            'quantity' => 2,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $draftInvoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Test',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Only active invoices can be refunded. Draft or voided invoices cannot be refunded.']);

        // 2. Voided invoice
        $draftInvoice->update(['status' => 'voided']);

        $responseVoided = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $draftInvoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Test',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $responseVoided->assertStatus(422);
    }

    /**
     * Test cashier cannot refund an invoice belonging to another branch.
     */
    public function test_cashier_branch_isolation_blocks_refund_for_another_branch(): void
    {
        // Invoice belongs to Branch B
        $invoiceB = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 50,
            'net_total' => 50,
            'paid_amount_cash' => 50,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoiceB->id,
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 50,
            'quantity' => 1,
            'subtotal' => 50,
        ]);

        // Cashier A attempts to view details for Branch B invoice
        $detailsResponse = $this->actingAs($this->cashierUserA)
            ->getJson(route('refunds.invoice_details', $invoiceB->id));
        $detailsResponse->assertStatus(403);

        // Cashier A attempts to create refund for Branch B invoice
        $createResponse = $this->actingAs($this->cashierUserA)
            ->get(route('refunds.create', ['invoice_id' => $invoiceB->id]));
        $createResponse->assertStatus(403);

        // Cashier A attempts to submit refund for Branch B invoice
        $submitResponse = $this->actingAs($this->cashierUserA)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoiceB->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Unauthorized attempt',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $submitResponse->assertStatus(422);
        $this->assertEquals(0, Refund::count());
    }

    /**
     * Test partial product return restores inventory and records transaction.
     */
    public function test_partial_product_return_restores_inventory_and_records_transaction(): void
    {
        // Setup initial inventory stock
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        // Active invoice with 5 products
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100,
            'net_total' => 100,
            'paid_amount_cash' => 100,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 20.00,
            'quantity' => 5,
            'subtotal' => 100.00,
        ]);

        // Refund 2 units
        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Customer returned 2 unopened units',
            'notes' => 'Returned in good condition',
            'items' => [
                [
                    'sales_invoice_detail_id' => $detail->id,
                    'quantity' => 2,
                    'inventory_id' => $this->inventoryA->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 1. Verify Refund record
        $refund = Refund::first();
        $this->assertNotNull($refund);
        $this->assertEquals(40.00, (float) $refund->total_refund_amount);
        $this->assertEquals('cash', $refund->refund_method);
        $this->assertEquals($invoice->id, $refund->sales_invoice_id);

        // 2. Verify RefundDetail record
        $refundDetail = RefundDetail::first();
        $this->assertNotNull($refundDetail);
        $this->assertEquals(2, $refundDetail->quantity);
        $this->assertEquals(40.00, (float) $refundDetail->subtotal);
        $this->assertTrue((bool) $refundDetail->inventory_restored);
        $this->assertEquals($this->inventoryA->id, $refundDetail->inventory_id);

        // 3. Verify physical inventory restored (10 + 2 = 12)
        $invProduct = InventoryProduct::where('inventory_id', $this->inventoryA->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(12, $invProduct->quantity);

        // 4. Verify InventoryTransaction recorded
        $invTxn = InventoryTransaction::where('transaction_type', 'sales_return')->first();
        $this->assertNotNull($invTxn);
        $this->assertEquals($this->inventoryA->id, $invTxn->destination_inventory_id);

        // 5. Verify SalesInvoice updated
        $invoice->refresh();
        $this->assertEquals('partial', $invoice->refund_status);
        $this->assertEquals(40.00, (float) $invoice->total_refunded);
        $this->assertEquals(60.00, $invoice->remainingRefundableAmount());

        // 6. Verify SalesInvoiceDetail tracking
        $detail->refresh();
        $this->assertEquals(2, $detail->refunded_quantity);
        $this->assertEquals(40.00, (float) $detail->refunded_amount);
        $this->assertEquals(3, $detail->remainingRefundableQuantity());
    }

    /**
     * Test service refund reverses employee commission.
     */
    public function test_service_refund_reverses_employee_commission(): void
    {
        // Active invoice with 2 services, total $100, commission $20 ($10 each)
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100,
            'net_total' => 100,
            'paid_amount_cash' => 100,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 50.00,
            'quantity' => 2,
            'discount' => 0,
            'subtotal' => 100.00,
            'commission_type' => 'percentage',
            'commission_rate' => 20.00,
            'commission_amount' => 20.00,
        ]);

        // Refund 1 service
        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Customer unhappy with hair trim',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200);

        $refund = Refund::first();
        $this->assertEquals(50.00, (float) $refund->total_refund_amount);
        $this->assertEquals(10.00, (float) $refund->commission_reversed_amount);

        $refundDetail = RefundDetail::first();
        $this->assertEquals(10.00, (float) $refundDetail->commission_reversed);
        $this->assertFalse((bool) $refundDetail->inventory_restored);

        // SalesInvoiceDetail commission_amount decremented by $10
        $detail->refresh();
        $this->assertEquals(10.00, (float) $detail->commission_amount);
        $this->assertEquals(1, $detail->refunded_quantity);
    }

    /**
     * Test refund to customer deposit credits available deposit balance.
     */
    public function test_refund_with_deposit_credit_increases_customer_deposit_balance(): void
    {
        $this->assertEquals(0.00, $this->customer->getAvailableDepositAmount());

        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 75,
            'net_total' => 75,
            'paid_amount_cash' => 75,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 75.00,
            'quantity' => 1,
            'subtotal' => 75.00,
        ]);

        // Process refund with refund_method = 'deposit'
        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'deposit',
            'reason' => 'Service credit for rescheduling',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200);

        // Verify CustomerTransaction created
        $custTxn = CustomerTransaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($custTxn);
        $this->assertEquals(75.00, (float) $custTxn->amount);
        $this->assertEquals('available', $custTxn->status);
        $this->assertEquals(Refund::class, $custTxn->reference_type);

        // Customer available deposit should now equal 75
        $this->assertEquals(75.00, $this->customer->getAvailableDepositAmount());
    }

    /**
     * Test full refund marks sales invoice refund_status as full.
     */
    public function test_full_refund_marks_invoice_refund_status_full(): void
    {
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 50,
            'net_total' => 50,
            'paid_amount_cash' => 50,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Full return',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('full', $invoice->refund_status);
        $this->assertEquals(0.0, $invoice->remainingRefundableAmount());
        $this->assertFalse($invoice->isRefundable());

        // Subsequent refund attempt rejected
        $secondAttempt = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Another refund',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        $secondAttempt->assertStatus(422);
    }

    /**
     * Test cannot refund quantity exceeding remaining available quantity.
     */
    public function test_cannot_refund_quantity_exceeding_remaining(): void
    {
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 40,
            'net_total' => 40,
            'paid_amount_cash' => 40,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 20.00,
            'quantity' => 2,
            'subtotal' => 40.00,
        ]);

        // Attempt to refund 3 when only 2 exist
        $response = $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Over refund test',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Refund::count());
    }

    /**
     * Test invoice with associated refunds cannot be voided.
     */
    public function test_cannot_void_invoice_with_associated_refunds(): void
    {
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100,
            'net_total' => 100,
            'paid_amount_cash' => 100,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $detail = SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'provider_id' => $this->provider->id,
            'customer_price' => 50.00,
            'quantity' => 2,
            'subtotal' => 100.00,
        ]);

        // 1. Process partial refund
        $this->actingAs($this->adminUser)->postJson(route('refunds.store'), [
            'sales_invoice_id' => $invoice->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'reason' => 'Partial refund',
            'items' => [
                ['sales_invoice_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ]);

        // 2. Attempt to void invoice via route
        $voidResponse = $this->actingAs($this->adminUser)->postJson(route('sales_invoices.void', $invoice->id), [
            'reason' => 'Attempting void on refunded invoice',
        ]);

        $voidResponse->assertStatus(422);
        $voidResponse->assertJsonFragment(['error' => 'Invoice cannot be voided because it has associated refunds. Process refunds for returns instead.']);
        $invoice->refresh();
        $this->assertEquals('active', $invoice->status);

        // 3. Directly calling void() throws Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice cannot be voided because it has associated refunds.');
        $invoice->void('Direct void attempt');
    }

    /**
     * Test refunds DataTables endpoint and branch isolation.
     */
    public function test_refunds_datatables_endpoint_and_branch_scoping(): void
    {
        // Refund at Branch A
        $invoiceA = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 50,
            'net_total' => 50,
            'paid_amount_cash' => 50,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);
        Refund::create([
            'refund_number' => 'REF-20260925-0001',
            'sales_invoice_id' => $invoiceA->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'cash',
            'total_refund_amount' => 50.00,
            'reason' => 'Branch A Return',
            'created_by' => $this->adminUser->id,
        ]);

        // Refund at Branch B
        $invoiceB = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'total_amount' => 70,
            'net_total' => 70,
            'paid_amount_cash' => 70,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->adminUser->id,
        ]);
        Refund::create([
            'refund_number' => 'REF-20260925-0002',
            'sales_invoice_id' => $invoiceB->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchB->id,
            'refund_date' => now()->toDateString(),
            'refund_method' => 'card',
            'total_refund_amount' => 70.00,
            'reason' => 'Branch B Return',
            'created_by' => $this->adminUser->id,
        ]);

        // Admin sees both
        $adminRes = $this->actingAs($this->adminUser)->getJson(route('refunds.index'));
        $adminRes->assertStatus(200);
        $this->assertCount(2, $adminRes->json('data'));

        // Cashier A sees only Branch A
        $cashierRes = $this->actingAs($this->cashierUserA)->getJson(route('refunds.index'));
        $cashierRes->assertStatus(200);
        $this->assertCount(1, $cashierRes->json('data'));
        $this->assertEquals('REF-20260925-0001', $cashierRes->json('data')[0]['refund_number']);
    }

    /**
     * Test Store Balance Report factors sales_returns into in_qty.
     */
    public function test_store_balance_report_includes_sales_returns_in_in_qty(): void
    {
        InventoryProduct::create([
            'inventory_id' => $this->inventoryA->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
        ]);

        $firstDayOfMonth = now()->startOfMonth()->toDateString();
        $lastDayOfMonth = now()->endOfMonth()->toDateString();

        // Create a sales_return inventory transaction
        $txn = InventoryTransaction::create([
            'transaction_type' => 'sales_return',
            'destination_inventory_id' => $this->inventoryA->id,
            'total_before_discount' => 60,
            'net_total' => 60,
        ]);

        InventoryTransactionDetail::create([
            'inventory_transaction_id' => $txn->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('report.stock_balance_transfer', [
            'firstDayOfMonth' => $firstDayOfMonth,
            'lastDayOfMonth' => $lastDayOfMonth,
            'inventory_id' => $this->inventoryA->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['data']);

        $productRow = collect($data['data'])->firstWhere('id', $this->product->id);
        $this->assertNotNull($productRow);
        $this->assertEquals(3, (int) $productRow['in_qty']);
    }

    /**
     * Test daily revenue report exposes total refunds.
     */
    public function test_daily_revenue_report_reflects_refunds(): void
    {
        $today = now()->toDateString();

        $invoice = SalesInvoice::create([
            'invoice_date' => $today,
            'total_amount' => 100,
            'net_total' => 100,
            'paid_amount_cash' => 100,
            'payment_method_id' => $this->cashMethod->id,
            'balance_due' => 0,
            'status' => 'active',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'created_by' => $this->adminUser->id,
        ]);

        Refund::create([
            'refund_number' => 'REF-20260925-0099',
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchA->id,
            'refund_date' => $today,
            'refund_method' => 'cash',
            'total_refund_amount' => 35.00,
            'reason' => 'Daily revenue test refund',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('report.daily_revenues', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $this->branchA->id,
        ]));

        $response->assertStatus(200);
        $this->assertEquals(35.00, (float) $response->json('total_refunds'));
    }
}
