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
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceProduct;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceVoidWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;

    protected User $unauthorizedUser;

    protected Branch $branch;

    protected Inventory $inventory;

    protected Customer $customer;

    protected Employee $provider;

    protected PaymentMethod $paymentMethod;

    protected ServiceCategory $serviceCategory;

    protected ProductCategory $productCategory;

    protected Unit $unit;

    protected Product $retailProduct;

    protected Product $consumableProduct;

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
            'sales_invoices.void',
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

        $this->unauthorizedUser = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauth_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        // Only index permission, no void permission
        $this->unauthorizedUser->givePermissionTo(['sales_invoices.index']);

        AdminPanelSetting::create([
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'block_insufficient_consumables' => false,
            'void_time_window_hours' => 24,
            'created_by' => $this->cashierUser->id,
        ]);

        \Illuminate\Support\Facades\View::share('adminPanelSetting', AdminPanelSetting::first());

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
            'name' => 'Jane Doe',
            'email' => 'jane_'.uniqid().'@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Sarah Provider',
            'branch_id' => $this->branch->id,
            'employee_level_id' => $level->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Hair Care',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->productCategory = ProductCategory::create([
            'name' => 'Salon Supplies',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->unit = Unit::create([
            'name' => 'Bottle',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->retailProduct = Product::create([
            'name' => 'Argan Oil Retail',
            'code' => 'RET-'.uniqid(),
            'price' => 40.00,
            'cost' => 20.00,
            'type' => 'sales',
            'product_category_id' => $this->productCategory->id,
            'unit_id' => $this->unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->consumableProduct = Product::create([
            'name' => 'Salon Shampoo Consumable',
            'code' => 'CON-'.uniqid(),
            'price' => 20.00,
            'cost' => 10.00,
            'type' => 'operation',
            'product_category_id' => $this->productCategory->id,
            'unit_id' => $this->unit->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->service = Service::create([
            'name' => 'Haircut & Wash',
            'description' => 'Haircut & Wash Service',
            'duration' => '60',
            'price' => 50.00,
            'price_can_change' => false,
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        ServiceProduct::create([
            'service_id' => $this->service->id,
            'product_id' => $this->consumableProduct->id,
            'product_quantity' => 2,
        ]);
    }

    protected function createInvoice(array $attributes = []): SalesInvoice
    {
        return SalesInvoice::create(array_merge([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 50.00,
            'invoice_discount' => 0,
            'invoice_deposit' => 0,
            'invoice_tax' => 0,
            'net_total' => 50.00,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_method_value' => 0,
            'balance_due' => 0,
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ], $attributes));
    }

    public function test_authorized_user_can_void_active_invoice_with_valid_reason_and_within_window(): void
    {
        $invoice = $this->createInvoice([
            'total_amount' => 50.00,
            'net_total' => 50.00,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Customer requested a full refund and cancellation.',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Invoice voided successfully.',
            'status' => 'voided',
            'void_reason' => 'Customer requested a full refund and cancellation.',
        ]);

        $invoice->refresh();
        $this->assertEquals('voided', $invoice->status);
        $this->assertEquals($this->cashierUser->id, $invoice->voided_by);
        $this->assertNotNull($invoice->voided_at);
        $this->assertEquals('Customer requested a full refund and cancellation.', $invoice->void_reason);
    }

    public function test_voiding_active_invoice_restores_retail_product_inventory(): void
    {
        // Initial inventory: 7 (3 already deducted upon invoice activation)
        $invProduct = InventoryProduct::create([
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->retailProduct->id,
            'quantity' => 7,
        ]);

        $invoice = $this->createInvoice([
            'total_amount' => 120.00,
            'net_total' => 120.00,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $this->retailProduct->id,
            'quantity' => 3,
            'cost_price' => 20.00,
            'customer_price' => 40.00,
            'provider_id' => $this->provider->id,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Damaged goods return',
            ]);

        $response->assertStatus(200);

        // 7 + 3 = 10 restored
        $invProduct->refresh();
        $this->assertEquals(10, $invProduct->quantity);
    }

    public function test_voiding_active_invoice_restores_service_consumables_inventory(): void
    {
        // Service requires 2 units of consumableProduct. Quantity = 2 services => 4 consumables deducted.
        // Initial consumable stock: 6 (4 already deducted upon activation from initial 10)
        $invProduct = InventoryProduct::create([
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->consumableProduct->id,
            'quantity' => 6,
        ]);

        $invoice = $this->createInvoice([
            'total_amount' => 100.00,
            'net_total' => 100.00,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'quantity' => 2,
            'cost_price' => 0,
            'customer_price' => 50.00,
            'provider_id' => $this->provider->id,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Service not performed, appointment cancelled',
            ]);

        $response->assertStatus(200);

        // 6 + 4 = 10 restored
        $invProduct->refresh();
        $this->assertEquals(10, $invProduct->quantity);
    }

    public function test_voiding_invoice_restores_partially_consumed_customer_deposit(): void
    {
        // Customer made a deposit of 100
        $deposit = CustomerTransaction::create([
            'customer_id' => $this->customer->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 60.00, // 40 used by invoice
            'notes' => 'deposit',
            'status' => 'available',
            'created_by' => $this->cashierUser->id,
        ]);

        $invoice = $this->createInvoice([
            'total_amount' => 40.00,
            'net_total' => 40.00,
        ]);

        $usage = CustomerTransaction::create([
            'customer_id' => $this->customer->id,
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'amount' => -40.00,
            'notes' => 'Deposit usage for invoice #'.$invoice->id,
            'status' => 'used',
            'used_in_transaction_id' => $deposit->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $this->assertEquals(60.00, $this->customer->getAvailableDepositAmount());

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Invoice created in error, refund deposit',
            ]);

        $response->assertStatus(200);

        // Deposit restored: 60 + 40 = 100
        $deposit->refresh();
        $this->assertEquals(100.00, $deposit->amount);
        $this->assertEquals('available', $deposit->status);
        $this->assertEquals(100.00, $this->customer->getAvailableDepositAmount());

        // Usage record deleted
        $this->assertDatabaseMissing('customer_transactions', [
            'id' => $usage->id,
        ]);
    }

    public function test_voiding_invoice_restores_fully_consumed_customer_deposit_without_doubling(): void
    {
        // When fully consumed, status is 'used' and amount remains 100 in the original ledger
        $deposit = CustomerTransaction::create([
            'customer_id' => $this->customer->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 100.00,
            'notes' => 'deposit',
            'status' => 'used',
            'created_by' => $this->cashierUser->id,
        ]);

        $invoice = $this->createInvoice([
            'total_amount' => 100.00,
            'net_total' => 100.00,
        ]);

        $usage = CustomerTransaction::create([
            'customer_id' => $this->customer->id,
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'amount' => -100.00,
            'notes' => 'Deposit usage for invoice #'.$invoice->id,
            'status' => 'used',
            'used_in_transaction_id' => $deposit->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $this->assertEquals(0, $this->customer->getAvailableDepositAmount());

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Cancellation of invoice',
            ]);

        $response->assertStatus(200);

        // Deposit restored to available with exact original 100 (never 200)
        $deposit->refresh();
        $this->assertEquals('available', $deposit->status);
        $this->assertEquals(100.00, $deposit->amount);
        $this->assertEquals(100.00, $this->customer->getAvailableDepositAmount());

        $this->assertDatabaseMissing('customer_transactions', [
            'id' => $usage->id,
        ]);
    }

    public function test_voiding_recomputes_customer_last_service(): void
    {
        // 1. Earlier service invoice on 2026-09-10
        $invoice1 = $this->createInvoice([
            'invoice_date' => '2026-09-10 10:00:00',
            'total_amount' => 50.00,
            'net_total' => 50.00,
        ]);
        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice1->id,
            'service_id' => $this->service->id,
            'quantity' => 1,
            'cost_price' => 0,
            'customer_price' => 50.00,
            'provider_id' => $this->provider->id,
        ]);

        // 2. Later service invoice on 2026-09-20
        $invoice2 = $this->createInvoice([
            'invoice_date' => '2026-09-20 14:00:00',
            'total_amount' => 50.00,
            'net_total' => 50.00,
        ]);
        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice2->id,
            'service_id' => $this->service->id,
            'quantity' => 1,
            'cost_price' => 0,
            'customer_price' => 50.00,
            'provider_id' => $this->provider->id,
        ]);

        $this->customer->update(['last_service' => '2026-09-20 14:00:00']);

        // Void invoice 2
        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice2->id), [
                'reason' => 'Voiding recent appointment',
            ]);

        $response->assertStatus(200);

        // Customer's last service rolled back to invoice 1
        $this->customer->refresh();
        $this->assertEquals('2026-09-10 10:00:00', $this->customer->last_service);
    }

    public function test_cannot_void_already_voided_invoice(): void
    {
        $invoice = $this->createInvoice([
            'status' => 'voided',
            'voided_by' => $this->cashierUser->id,
            'voided_at' => now(),
            'void_reason' => 'First void',
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Second void attempt',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'error' => 'Invoice is already voided.',
        ]);
    }

    public function test_cannot_void_invoice_outside_time_window(): void
    {
        // Window is 24 hours. Created 48 hours ago.
        $invoice = $this->createInvoice([
            'invoice_date' => now()->subHours(48)->toDateString(),
        ]);

        $invoice->created_at = now()->subHours(48);
        $invoice->save();

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Trying to void old invoice',
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Void window has expired', $response->json('error'));
    }

    public function test_unauthorized_user_cannot_void_invoice(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => 'Unauthorized void attempt',
            ]);

        $response->assertStatus(403);
    }

    public function test_void_requires_non_empty_reason(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.void', $invoice->id), [
                'reason' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_voided_invoices_cannot_be_activated_or_deleted(): void
    {
        $invoice = $this->createInvoice([
            'status' => 'voided',
            'voided_by' => $this->cashierUser->id,
            'voided_at' => now(),
            'void_reason' => 'Cancelled',
        ]);

        // Attempt activate via update
        $updateResponse = $this->actingAs($this->cashierUser)
            ->put(route('sales_invoices.update', $invoice->id), [
                'status' => 'active',
            ]);
        $updateResponse->assertStatus(404);

        // Attempt delete
        $deleteResponse = $this->actingAs($this->cashierUser)
            ->delete(route('sales_invoices.destroy', $invoice->id));
        $deleteResponse->assertStatus(403);
    }

    public function test_voided_invoices_are_excluded_from_active_reports(): void
    {
        $invoice = $this->createInvoice([
            'status' => 'active',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'net_total' => 100.00,
        ]);
        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'quantity' => 1,
            'cost_price' => 0,
            'customer_price' => 100.00,
            'provider_id' => $this->provider->id,
        ]);

        $this->assertEquals(1, SalesInvoice::where('status', 'active')->count());

        $invoice->void('Cancelled for customer', $this->cashierUser->id);

        $this->assertEquals('voided', $invoice->fresh()->status);
        $this->assertEquals(0, SalesInvoice::where('status', 'active')->count());
    }
}
