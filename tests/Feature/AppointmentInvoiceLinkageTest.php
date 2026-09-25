<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Http\Resources\AppointmentResource;
use App\Models\AdminPanelSetting;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for REQ-017: Appointment → Invoice Linkage & Conversion Analytics (GAP-012).
 */
class AppointmentInvoiceLinkageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $cashierA;

    protected User $cashierB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected Employee $employeeA;

    protected Employee $employeeB;

    protected Customer $customerA;

    protected Customer $customerB;

    protected PaymentMethod $cashMethod;

    protected ServiceCategory $serviceCategory;

    protected Service $serviceA;

    protected Service $serviceB;

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
            'sales_invoices.index',
            'sales_invoices.create',
            'sales_invoices.show',
            'sales_invoices.edit',
            'sales_invoices.destroy',
            'appointments.index',
            'appointments.show',
            'appointments.create',
            'appointments.edit',
            'appointments.destroy',
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

        $this->customerA = Customer::create([
            'name' => 'Alice Customer',
            'phone' => '01011112222',
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->customerB = Customer::create([
            'name' => 'Bob Customer',
            'phone' => '01033334444',
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['name' => 'cash'],
            ['status' => 'active', 'created_by' => 1]
        );

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Haircuts',
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->serviceA = Service::create([
            'name' => 'Alpha Styling',
            'duration' => 60,
            'price' => 150,
            'price_can_change' => false,
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
            'created_by' => 1,
        ]);

        $this->serviceB = Service::create([
            'name' => 'Beta Coloring',
            'duration' => 90,
            'price' => 250,
            'price_can_change' => false,
            'service_category_id' => $this->serviceCategory->id,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
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

    private function createAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $this->customerA->id,
            'provider_id' => $this->employeeA->id,
            'service_id' => $this->serviceA->id,
            'start_date' => now()->addHour()->format('Y-m-d H:i:s'),
            'end_date' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->adminUser->id,
        ], $overrides));
    }

    private function buildInvoicePayload(int $branchId, int $providerId, int $serviceId, float $price = 150, ?int $appointmentId = null, ?int $customerId = null): array
    {
        $payload = [
            'customer_id' => $customerId ?? $this->customerA->id,
            'invoice_date' => now()->toDateString(),
            'branch_id' => $branchId,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'cash_payment' => $price,
            'payment_method_value' => 0,
            'deposit' => 0,
            'items' => [[
                'type' => 'service',
                'item_id' => $serviceId,
                'code' => 'SRV-'.$serviceId,
                'price' => $price,
                'quantity' => 1,
                'provider_id' => $providerId,
                'discount' => 0,
                'tax' => 0,
            ]],
        ];

        if ($appointmentId !== null) {
            $payload['appointment_id'] = $appointmentId;
        }

        return $payload;
    }

    /**
     * Test schema has appointment_id column and foreign key relationship.
     */
    public function test_schema_has_appointment_id_and_relationships_work(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_invoices', 'appointment_id'));

        $appointment = $this->createAppointment();
        $invoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'payment_method_id' => $this->cashMethod->id,
            'status' => 'active',
            'appointment_id' => $appointment->id,
            'total_amount' => 150,
            'paid_amount' => 150,
            'balance_due' => 0,
            'created_by' => $this->adminUser->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $invoice->appointment);
        $this->assertEquals($appointment->id, $invoice->appointment->id);

        $this->assertInstanceOf(SalesInvoice::class, $appointment->salesInvoice);
        $this->assertEquals($invoice->id, $appointment->salesInvoice->id);
        $this->assertCount(1, $appointment->salesInvoices);
    }

    /**
     * Test GET sales_invoices.create?appointment_id=X pre-loads appointment and populates view data.
     */
    public function test_create_invoice_page_prepopulates_appointment_data(): void
    {
        $appointment = $this->createAppointment();

        $response = $this->actingAs($this->adminUser)
            ->get(route('sales_invoices.create', ['appointment_id' => $appointment->id]));

        $response->assertStatus(200);
        $response->assertViewHas('linkedAppointment');
        $response->assertSee((string) $appointment->id);
        $response->assertSee($this->customerA->name);
    }

    /**
     * Test Cashier B cannot access appointment belonging to Branch A on the create page (branch isolation).
     */
    public function test_cashier_cannot_open_create_page_for_appointment_in_other_branch(): void
    {
        $appointment = $this->createAppointment(); // belongs to branchA

        $response = $this->actingAs($this->cashierB)
            ->get(route('sales_invoices.create', ['appointment_id' => $appointment->id]));

        $response->assertStatus(403);
    }

    /**
     * Test storing active invoice with appointment_id creates invoice and auto-transitions appointment to completed.
     */
    public function test_storing_active_invoice_transitions_appointment_to_completed(): void
    {
        $appointment = $this->createAppointment(['status' => AppointmentStatus::CONFIRMED->value]);

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appointment->id
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');
        $this->assertNotNull($invoiceId);

        $invoice = SalesInvoice::find($invoiceId);
        $this->assertEquals($appointment->id, $invoice->appointment_id);

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::COMPLETED, $appointment->status);
    }

    /**
     * Test direct checkout transitions to completed from both confirmed and checked_in states.
     */
    public function test_direct_checkout_from_confirmed_and_checked_in_transitions_to_completed(): void
    {
        // 1. From CHECKED_IN
        $appCheckedIn = $this->createAppointment([
            'status' => AppointmentStatus::CHECKED_IN->value,
            'start_date' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'end_date' => now()->addHours(4)->format('Y-m-d H:i:s'),
        ]);

        $payload1 = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appCheckedIn->id
        );

        $response1 = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload1);
        $response1->assertStatus(200);

        $appCheckedIn->refresh();
        $this->assertEquals(AppointmentStatus::COMPLETED, $appCheckedIn->status);

        // 2. From IN_SERVICE
        $appInService = $this->createAppointment([
            'status' => AppointmentStatus::IN_SERVICE->value,
            'start_date' => now()->addHours(5)->format('Y-m-d H:i:s'),
            'end_date' => now()->addHours(6)->format('Y-m-d H:i:s'),
        ]);

        $payload2 = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appInService->id
        );

        $response2 = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload2);
        $response2->assertStatus(200);

        $appInService->refresh();
        $this->assertEquals(AppointmentStatus::COMPLETED, $appInService->status);
    }

    /**
     * Test storing a draft invoice keeps appointment status unchanged; activating draft transitions appointment to completed.
     */
    public function test_draft_invoice_leaves_appointment_status_until_activated(): void
    {
        $appointment = $this->createAppointment(['status' => AppointmentStatus::CONFIRMED->value]);

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appointment->id
        );
        $payload['status'] = 'draft';

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(200);
        $invoiceId = $response->json('invoice_id');

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->status, 'Draft invoice should not transition appointment.');

        // Now activate the draft invoice
        $activateResponse = $this->actingAs($this->adminUser)
            ->post(route('sales_invoices.activate', $invoiceId));

        $activateResponse->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::COMPLETED, $appointment->status, 'Activating draft invoice must transition appointment to completed.');
    }

    /**
     * Test validation rejects linking cancelled or no-show appointments.
     */
    public function test_validation_rejects_cancelled_or_no_show_appointments(): void
    {
        $cancelledApp = $this->createAppointment([
            'status' => AppointmentStatus::CANCELLED->value,
            'cancellation_reason' => 'Customer requested cancellation',
            'cancelled_at' => now(),
        ]);

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $cancelledApp->id
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_id']);
    }

    /**
     * Test validation rejects customer mismatch between invoice and appointment.
     */
    public function test_validation_rejects_customer_mismatch(): void
    {
        $appointment = $this->createAppointment(); // belongs to customerA

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appointment->id,
            customerId: $this->customerB->id // mismatch!
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_id']);
    }

    /**
     * Test validation prevents duplicate active invoices for the same appointment.
     */
    public function test_validation_prevents_duplicate_active_invoices_for_same_appointment(): void
    {
        $appointment = $this->createAppointment();

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appointment->id
        );

        // 1st invoice succeeds
        $firstResponse = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);
        $firstResponse->assertStatus(200);

        // 2nd invoice attempt for same appointment must fail validation
        $secondResponse = $this->actingAs($this->adminUser)
            ->postJson(route('sales_invoices.store'), $payload);

        $secondResponse->assertStatus(422);
        $secondResponse->assertJsonValidationErrors(['appointment_id']);
    }

    /**
     * Test Cashier A cannot checkout an appointment belonging to Branch B (server-side branch isolation).
     */
    public function test_cashier_cannot_checkout_appointment_belonging_to_another_branch(): void
    {
        $appointmentB = Appointment::create([
            'customer_id' => $this->customerB->id,
            'provider_id' => $this->employeeB->id,
            'service_id' => $this->serviceB->id,
            'start_date' => now()->addHour()->format('Y-m-d H:i:s'),
            'end_date' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->adminUser->id,
        ]);

        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appointmentB->id,
            customerId: $this->customerB->id
        );

        $response = $this->actingAs($this->cashierA)
            ->postJson(route('sales_invoices.store'), $payload);

        $response->assertStatus(403);
    }

    /**
     * Test AppointmentResource exposes can_checkout, checkout_url, and invoice_id.
     */
    public function test_appointment_resource_exposes_checkout_metadata(): void
    {
        $appointment = $this->createAppointment(['status' => AppointmentStatus::CONFIRMED->value]);

        $resource = (new AppointmentResource($appointment))->toArray(Request::create('/'));

        $this->assertTrue($resource['can_checkout']);
        $this->assertStringContainsString((string) $appointment->id, $resource['checkout_url']);
        $this->assertNull($resource['invoice_id']);

        // Test with cancelled appointment
        $cancelledApp = $this->createAppointment([
            'status' => AppointmentStatus::CANCELLED->value,
            'cancellation_reason' => 'No time',
            'cancelled_at' => now(),
            'start_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        ]);

        $cancelledResource = (new AppointmentResource($cancelledApp))->toArray(Request::create('/'));
        $this->assertFalse($cancelledResource['can_checkout']);
    }

    /**
     * Test Appointment Conversion report page renders and returns calculated stats JSON.
     */
    public function test_appointment_conversion_report_page_and_stats_api(): void
    {
        // Create 2 confirmed appointments, checkout 1 of them
        $app1 = $this->createAppointment([
            'start_date' => now()->format('Y-m-d 10:00:00'),
            'end_date' => now()->format('Y-m-d 11:00:00'),
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        $app2 = $this->createAppointment([
            'start_date' => now()->format('Y-m-d 12:00:00'),
            'end_date' => now()->format('Y-m-d 13:00:00'),
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        // Checkout app1
        $payload = $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $app1->id
        );
        $checkoutRes = $this->actingAs($this->adminUser)->postJson(route('sales_invoices.store'), $payload);
        $checkoutRes->assertStatus(200);

        // Check report HTML page
        $pageResponse = $this->actingAs($this->adminUser)
            ->get(route('report.appointment_conversion'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Appointment Conversion');

        // Check stats JSON endpoint
        $statsResponse = $this->actingAs($this->adminUser)
            ->getJson(route('report.appointment_conversion_stats', [
                'from_date' => now()->subDay()->toDateString(),
                'to_date' => now()->addDay()->toDateString(),
            ]));

        $statsResponse->assertStatus(200);
        $data = $statsResponse->json();

        $this->assertArrayHasKey('total_booked', $data);
        $this->assertArrayHasKey('provider_breakdown', $data);
        $this->assertArrayHasKey('service_breakdown', $data);

        $this->assertGreaterThanOrEqual(2, $data['total_booked']);
        $this->assertGreaterThanOrEqual(1, $data['completed_count']);
        $this->assertGreaterThanOrEqual(1, $data['invoiced_count']);
        $this->assertEquals('150.00', $data['total_revenue']);
        $this->assertGreaterThan(0, $data['confirmed_to_completed_rate']);
    }

    /**
     * Test Appointment Conversion stats respects branch filtering.
     */
    public function test_appointment_conversion_stats_respects_branch_filtering(): void
    {
        // Branch A appointment
        $appA = $this->createAppointment([
            'start_date' => now()->format('Y-m-d 10:00:00'),
            'end_date' => now()->format('Y-m-d 11:00:00'),
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);
        $this->actingAs($this->adminUser)->postJson(route('sales_invoices.store'), $this->buildInvoicePayload(
            branchId: $this->branchA->id,
            providerId: $this->employeeA->id,
            serviceId: $this->serviceA->id,
            price: 150,
            appointmentId: $appA->id
        ));

        // Branch B appointment
        $appB = Appointment::create([
            'customer_id' => $this->customerB->id,
            'provider_id' => $this->employeeB->id,
            'service_id' => $this->serviceB->id,
            'start_date' => now()->format('Y-m-d 14:00:00'),
            'end_date' => now()->format('Y-m-d 15:30:00'),
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->adminUser->id,
        ]);
        $this->actingAs($this->adminUser)->postJson(route('sales_invoices.store'), $this->buildInvoicePayload(
            branchId: $this->branchB->id,
            providerId: $this->employeeB->id,
            serviceId: $this->serviceB->id,
            price: 250,
            appointmentId: $appB->id,
            customerId: $this->customerB->id
        ));

        // Query branch A only
        $statsA = $this->actingAs($this->adminUser)
            ->getJson(route('report.appointment_conversion_stats', [
                'branch_id' => $this->branchA->id,
                'from_date' => now()->subDay()->toDateString(),
                'to_date' => now()->addDay()->toDateString(),
            ]));

        $statsA->assertStatus(200);
        $this->assertEquals('150.00', $statsA->json('total_revenue'));

        // Query branch B only
        $statsB = $this->actingAs($this->adminUser)
            ->getJson(route('report.appointment_conversion_stats', [
                'branch_id' => $this->branchB->id,
                'from_date' => now()->subDay()->toDateString(),
                'to_date' => now()->addDay()->toDateString(),
            ]));

        $statsB->assertStatus(200);
        $this->assertEquals('250.00', $statsB->json('total_revenue'));
    }
}
