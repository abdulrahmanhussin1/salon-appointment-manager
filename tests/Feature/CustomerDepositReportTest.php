<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerDepositReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $unauthorizedUser;

    protected User $cashierA;

    protected User $cashierB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected Employee $employeeA;

    protected Employee $employeeB;

    protected Customer $customerA;

    protected Customer $customerB;

    protected Customer $customerC;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('adminPanelSetting', (object) [
            'system_name' => 'Salon Manager',
            'system_logo' => null,
            'void_time_window_hours' => 24,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->unauthorizedUser = User::create([
            'name' => 'No Permissions User',
            'email' => 'unauth_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        Permission::firstOrCreate(
            ['name' => 'reports.index', 'guard_name' => 'web'],
            ['group' => 'reports']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $adminRole->givePermissionTo('reports.index');
        $cashierRole->givePermissionTo('reports.index');

        $this->adminUser->assignRole('admin');

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
            'name' => 'Staff Alpha',
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->employeeB = Employee::create([
            'name' => 'Staff Beta',
            'status' => 'active',
            'branch_id' => $this->branchB->id,
            'employee_level_id' => $level->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->cashierA = User::create([
            'name' => 'Cashier Alpha',
            'email' => 'cashier_a_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeA->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierA->assignRole('cashier');

        $this->cashierB = User::create([
            'name' => 'Cashier Beta',
            'email' => 'cashier_b_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'employee_id' => $this->employeeB->id,
            'created_by' => $this->adminUser->id,
        ]);
        $this->cashierB->assignRole('cashier');

        $this->customerA = Customer::create([
            'name' => 'Customer Alpha',
            'phone' => '01011111111',
            'status' => 'active',
            'created_by' => $this->cashierA->id,
        ]);

        $this->customerB = Customer::create([
            'name' => 'Customer Beta',
            'phone' => '01022222222',
            'status' => 'active',
            'created_by' => $this->cashierB->id,
        ]);

        $this->customerC = Customer::create([
            'name' => 'Customer Charlie',
            'phone' => '01033333333',
            'status' => 'active',
            'created_by' => $this->cashierA->id,
        ]);
    }

    public function test_guest_cannot_access_customer_deposits_pages_or_endpoints(): void
    {
        $this->get(route('report.customer_deposits'))
            ->assertRedirect(route('login'));

        $this->get(route('report.customer_deposits_data'))
            ->assertRedirect(route('login'));

        $this->get(route('report.customer_deposits_stats'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_reports_index_permission_is_forbidden(): void
    {
        $this->actingAs($this->unauthorizedUser);

        $this->get(route('report.customer_deposits'))
            ->assertStatus(403);

        $this->get(route('report.customer_deposits_data'))
            ->assertStatus(403);

        $this->get(route('report.customer_deposits_stats'))
            ->assertStatus(403);
    }

    public function test_admin_can_view_customer_deposits_page(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('report.customer_deposits'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pages.reports.customer_deposits');
        $response->assertViewHas('branches');
        $response->assertViewHas('canSelectAll', true);
    }

    public function test_admin_can_retrieve_aggregate_stats_across_all_branches(): void
    {
        $this->actingAs($this->adminUser);

        // Customer A: Deposited 500 in Branch A. Partially used 150 on invoice. Remaining: 350.
        $depA = CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 350.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'invoice',
            'reference_id' => 1,
            'amount' => -150.00,
            'status' => 'used',
            'used_in_transaction_id' => $depA->id,
            'created_by' => $this->cashierA->id,
        ]);

        // Customer B: Deposited 300 in Branch B. Unused. Remaining: 300.
        CustomerTransaction::create([
            'customer_id' => $this->customerB->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 300.00,
            'status' => 'available',
            'created_by' => $this->cashierB->id,
        ]);

        $response = $this->getJson(route('report.customer_deposits_stats'));

        $response->assertStatus(200);
        $response->assertJson([
            'total_liability' => '650.00',
            'total_deposited' => '800.00',
            'total_used' => '150.00',
            'customers_count' => 2,
        ]);
    }

    public function test_admin_can_filter_stats_by_branch(): void
    {
        $this->actingAs($this->adminUser);

        // Branch A transaction
        $depA = CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 350.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'invoice',
            'reference_id' => 1,
            'amount' => -150.00,
            'status' => 'used',
            'used_in_transaction_id' => $depA->id,
            'created_by' => $this->cashierA->id,
        ]);

        // Branch B transaction
        CustomerTransaction::create([
            'customer_id' => $this->customerB->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 300.00,
            'status' => 'available',
            'created_by' => $this->cashierB->id,
        ]);

        // Query Branch Alpha
        $responseA = $this->getJson(route('report.customer_deposits_stats', ['branch_id' => $this->branchA->id]));
        $responseA->assertStatus(200);
        $responseA->assertJson([
            'total_liability' => '350.00',
            'total_deposited' => '500.00',
            'total_used' => '150.00',
            'customers_count' => 1,
        ]);

        // Query Branch Beta
        $responseB = $this->getJson(route('report.customer_deposits_stats', ['branch_id' => $this->branchB->id]));
        $responseB->assertStatus(200);
        $responseB->assertJson([
            'total_liability' => '300.00',
            'total_deposited' => '300.00',
            'total_used' => '0.00',
            'customers_count' => 1,
        ]);
    }

    public function test_cashier_is_strictly_scoped_to_assigned_branch_for_stats(): void
    {
        $this->actingAs($this->cashierA);

        // Branch A transaction
        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 400.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        // Branch B transaction
        CustomerTransaction::create([
            'customer_id' => $this->customerB->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 700.00,
            'status' => 'available',
            'created_by' => $this->cashierB->id,
        ]);

        // Cashier A calls stats without branch_id -> Scoped to Branch A
        $response1 = $this->getJson(route('report.customer_deposits_stats'));
        $response1->assertStatus(200);
        $response1->assertJson([
            'total_liability' => '400.00',
            'total_deposited' => '400.00',
            'total_used' => '0.00',
            'customers_count' => 1,
        ]);

        // Cashier A attempts to query Branch B -> Forbidden override, still gets Branch A
        $response2 = $this->getJson(route('report.customer_deposits_stats', ['branch_id' => $this->branchB->id]));
        $response2->assertStatus(200);
        $response2->assertJson([
            'total_liability' => '400.00',
            'total_deposited' => '400.00',
            'total_used' => '0.00',
            'customers_count' => 1,
        ]);
    }

    public function test_data_endpoint_returns_datatables_json(): void
    {
        $this->actingAs($this->adminUser);

        // Customer A: 500 deposited, 150 used, 350 available
        $depA = CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 350.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'invoice',
            'reference_id' => 1,
            'amount' => -150.00,
            'status' => 'used',
            'used_in_transaction_id' => $depA->id,
            'created_by' => $this->cashierA->id,
        ]);

        // Customer B: 300 deposited, 0 used, 300 available
        CustomerTransaction::create([
            'customer_id' => $this->customerB->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 300.00,
            'status' => 'available',
            'created_by' => $this->cashierB->id,
        ]);

        $response = $this->getJson(route('report.customer_deposits_data'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);

        $customerAName = collect($data)->firstWhere('customer_name', 'Customer Alpha');
        $this->assertNotNull($customerAName);
        $this->assertEquals('500.00', $customerAName['total_deposited']);
        $this->assertEquals('150.00', $customerAName['total_used']);
        $this->assertEquals('350.00', $customerAName['current_balance']);
        $this->assertStringContainsString('350.00', $customerAName['balance_badge']);

        $customerBName = collect($data)->firstWhere('customer_name', 'Customer Beta');
        $this->assertNotNull($customerBName);
        $this->assertEquals('300.00', $customerBName['total_deposited']);
        $this->assertEquals('0.00', $customerBName['total_used']);
        $this->assertEquals('300.00', $customerBName['current_balance']);
    }

    public function test_cashier_is_strictly_scoped_to_assigned_branch_for_data(): void
    {
        $this->actingAs($this->cashierA);

        // Branch A transaction
        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 450.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        // Branch B transaction
        CustomerTransaction::create([
            'customer_id' => $this->customerB->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 600.00,
            'status' => 'available',
            'created_by' => $this->cashierB->id,
        ]);

        // Cashier A calls data: should only see Customer Alpha
        $response = $this->getJson(route('report.customer_deposits_data'));
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Customer Alpha', $data[0]['customer_name']);

        // Cashier A attempts to query Branch B
        $responseB = $this->getJson(route('report.customer_deposits_data', ['branch_id' => $this->branchB->id]));
        $responseB->assertStatus(200);

        $dataB = $responseB->json('data');
        $this->assertCount(1, $dataB);
        $this->assertEquals('Customer Alpha', $dataB[0]['customer_name']);
    }

    public function test_balance_filter_excludes_zero_balance_customers_by_default(): void
    {
        $this->actingAs($this->adminUser);

        // Customer A: Positive balance (250)
        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 250.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        // Customer C: Fully used balance (0 remaining)
        $depC = CustomerTransaction::create([
            'customer_id' => $this->customerC->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 0.00,
            'status' => 'used',
            'created_by' => $this->cashierA->id,
        ]);

        CustomerTransaction::create([
            'customer_id' => $this->customerC->id,
            'reference_type' => 'invoice',
            'reference_id' => 9,
            'amount' => -100.00,
            'status' => 'used',
            'used_in_transaction_id' => $depC->id,
            'created_by' => $this->cashierA->id,
        ]);

        // Default / 'positive': Customer C must NOT be returned
        $responsePositive = $this->getJson(route('report.customer_deposits_data', ['balance_filter' => 'positive']));
        $responsePositive->assertStatus(200);

        $dataPositive = $responsePositive->json('data');
        $this->assertCount(1, $dataPositive);
        $this->assertEquals('Customer Alpha', $dataPositive[0]['customer_name']);

        // 'all': Both Customer A and Customer C must be returned
        $responseAll = $this->getJson(route('report.customer_deposits_data', ['balance_filter' => 'all']));
        $responseAll->assertStatus(200);

        $dataAll = $responseAll->json('data');
        $this->assertCount(2, $dataAll);
        $charlie = collect($dataAll)->firstWhere('customer_name', 'Customer Charlie');
        $this->assertNotNull($charlie);
        $this->assertEquals('0.00', $charlie['current_balance']);
        $this->assertEquals('100.00', $charlie['total_used']);
        $this->assertEquals('100.00', $charlie['total_deposited']);
    }

    public function test_voiding_sales_invoice_reverses_deposit_usage_in_report(): void
    {
        $this->actingAs($this->adminUser);

        // Setup service and invoice
        $category = ServiceCategory::create(['name' => 'Hair', 'status' => 'active', 'created_by' => $this->adminUser->id]);
        $unit = Unit::create(['name' => 'Session', 'status' => 'active', 'branch_id' => $this->branchA->id, 'created_by' => $this->adminUser->id]);
        $cashMethod = PaymentMethod::create(['name' => 'Cash', 'status' => 'active', 'created_by' => $this->adminUser->id]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 200,
            'status' => 'active',
            'branch_id' => $this->branchA->id,
            'service_category_id' => $category->id,
            'unit_id' => $unit->id,
            'created_by' => $this->adminUser->id,
        ]);

        $deposit = CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'deposit',
            'reference_id' => 0,
            'amount' => 500.00,
            'status' => 'available',
            'created_by' => $this->cashierA->id,
        ]);

        // Invoice created using 200 of deposit
        $invoice = SalesInvoice::create([
            'invoice_date' => Carbon::now()->toDateString(),
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'total_amount' => 200,
            'invoice_discount' => 0,
            'invoice_deposit' => 200,
            'invoice_tax' => 0,
            'net_total' => 200,
            'paid_amount' => 200,
            'paid_amount_cash' => 0,
            'payment_method_id' => $cashMethod->id,
            'payment_method_value' => 0,
            'balance_due' => 0,
            'deposit_used_amount' => 200,
            'status' => 'active',
            'created_by' => $this->cashierA->id,
        ]);

        SalesInvoiceDetail::create([
            'sales_invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'product_id' => null,
            'provider_id' => $this->employeeA->id,
            'quantity' => 1,
            'unit_price' => 200,
            'customer_price' => 200,
            'total_price' => 200,
            'commission_rate' => 0,
            'commission_amount' => 0,
            'created_by' => $this->cashierA->id,
        ]);

        $deposit->amount -= 200;
        $deposit->save();

        CustomerTransaction::create([
            'customer_id' => $this->customerA->id,
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'amount' => -200,
            'notes' => 'Deposit usage for invoice #'.$invoice->id,
            'status' => 'used',
            'used_in_transaction_id' => $deposit->id,
            'created_by' => $this->cashierA->id,
        ]);

        // Verify state prior to void: 300 available, 200 used, 500 deposited
        $statsPre = $this->getJson(route('report.customer_deposits_stats'))->json();
        $this->assertEquals('300.00', $statsPre['total_liability']);
        $this->assertEquals('200.00', $statsPre['total_used']);
        $this->assertEquals('500.00', $statsPre['total_deposited']);

        // Void invoice: reverses deposit usage
        $invoice->reverseCustomerDepositUsage();
        $invoice->status = 'voided';
        $invoice->save();

        // Verify state post-void: 500 available, 0 used, 500 deposited
        $statsPost = $this->getJson(route('report.customer_deposits_stats'))->json();
        $this->assertEquals('500.00', $statsPost['total_liability']);
        $this->assertEquals('0.00', $statsPost['total_used']);
        $this->assertEquals('500.00', $statsPost['total_deposited']);

        $dataPost = $this->getJson(route('report.customer_deposits_data'))->json('data');
        $this->assertEquals('500.00', $dataPost[0]['current_balance']);
        $this->assertEquals('0.00', $dataPost[0]['total_used']);
        $this->assertEquals('500.00', $dataPost[0]['total_deposited']);
    }
}
