<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceCustomPriceTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;

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
            'name' => 'Alice Customer',
            'phone' => '1112223334',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $employeeLevel = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->cashierUser->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Stylist Sarah',
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
    }

    public function test_service_with_price_can_change_true_uses_submitted_custom_price(): void
    {
        $service = Service::create([
            'name' => 'Custom Hair Coloring',
            'price' => 100.00,
            'price_can_change' => true,
            'duration' => 60,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $customPrice = 175.50;
        $quantity = 2;

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'cash_payment' => $customPrice * $quantity,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-COLOR',
                    'price' => $customPrice, // Cashier submitted custom price
                    'provider_id' => $this->provider->id,
                    'quantity' => $quantity,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');
        $this->assertNotNull($invoiceId);

        $invoice = SalesInvoice::findOrFail($invoiceId);
        $expectedTotal = $customPrice * $quantity; // 351.00

        $this->assertEquals($expectedTotal, (float) $invoice->total_amount);
        $this->assertEquals($expectedTotal, (float) $invoice->net_total);

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoice->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals($customPrice, (float) $detail->customer_price);
        $this->assertEquals($expectedTotal, (float) $detail->subtotal);
    }

    public function test_service_with_price_can_change_false_ignores_submitted_price_and_uses_catalog_price(): void
    {
        $service = Service::create([
            'name' => 'Standard Haircut',
            'price' => 80.00,
            'price_can_change' => false,
            'duration' => 30,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $tamperedOrSubmittedPrice = 25.00; // Cashier or request attempts to discount/override fixed service
        $quantity = 1;

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'cash_payment' => 80.00,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-CUT',
                    'price' => $tamperedOrSubmittedPrice,
                    'provider_id' => $this->provider->id,
                    'quantity' => $quantity,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $invoice = SalesInvoice::findOrFail($invoiceId);
        $this->assertEquals(80.00, (float) $invoice->total_amount, 'Should enforce catalog price of 80.00');
        $this->assertEquals(80.00, (float) $invoice->net_total);

        $detail = SalesInvoiceDetail::where('sales_invoice_id', $invoice->id)->first();
        $this->assertEquals(80.00, (float) $detail->customer_price);
        $this->assertEquals(80.00, (float) $detail->subtotal);
    }

    public function test_negative_submitted_price_is_rejected_by_validation(): void
    {
        $service = Service::create([
            'name' => 'Hair Wash',
            'price' => 30.00,
            'price_can_change' => true,
            'duration' => 15,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'cash_payment' => 0,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $service->id,
                    'code' => 'SRV-WASH',
                    'price' => -20.00, // Invalid negative price
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_mixed_services_with_different_price_can_change_flags(): void
    {
        $serviceA = Service::create([
            'name' => 'Service Adjustable',
            'price' => 100.00,
            'price_can_change' => true,
            'duration' => 45,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $serviceB = Service::create([
            'name' => 'Service Fixed',
            'price' => 200.00,
            'price_can_change' => false,
            'duration' => 60,
            'status' => 'active',
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->cashierUser->id,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branch->id,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'active',
            'cash_payment' => 350.00,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [
                [
                    'type' => 'service',
                    'item_id' => $serviceA->id,
                    'code' => 'SRV-A',
                    'price' => 150.00, // Custom price: 150 (adjusted from 100)
                    'provider_id' => $this->provider->id,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
                [
                    'type' => 'service',
                    'item_id' => $serviceB->id,
                    'code' => 'SRV-B',
                    'price' => 50.00, // Attempt to override fixed price: should be ignored, remains 200
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
        $invoiceId = $response->json('invoice_id');

        $invoice = SalesInvoice::findOrFail($invoiceId);
        // Expected total = 150 + 200 = 350
        $this->assertEquals(350.00, (float) $invoice->total_amount);

        $detailA = SalesInvoiceDetail::where('sales_invoice_id', $invoice->id)
            ->where('service_id', $serviceA->id)
            ->first();
        $this->assertEquals(150.00, (float) $detailA->customer_price);

        $detailB = SalesInvoiceDetail::where('sales_invoice_id', $invoice->id)
            ->where('service_id', $serviceB->id)
            ->first();
        $this->assertEquals(200.00, (float) $detailB->customer_price);
    }
}
