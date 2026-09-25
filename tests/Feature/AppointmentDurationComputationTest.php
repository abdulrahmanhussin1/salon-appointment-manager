<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentDurationComputationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Employee $provider;

    private ServiceCategory $category;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'appointments.index',
            'appointments.show',
            'appointments.create',
            'appointments.edit',
            'appointments.destroy',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web', 'group' => 'appointments']);
        }

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->admin->givePermissionTo($permissions);

        $this->customer = Customer::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'phone' => '01011122233',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
            'phone' => '01099988877',
            'employee_level_id' => $level->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_omitting_end_date_auto_computes_from_service_duration_in_minutes(): void
    {
        $service = Service::create([
            'name' => 'Express Blowout',
            'service_category_id' => $this->category->id,
            'price' => 50.00,
            'duration' => 45, // 45 minutes
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            // end_date is omitted
        ]);

        $response->assertStatus(201);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame('2026-10-01 10:00:00', $appointment->start_date);
        $this->assertSame('2026-10-01 10:45:00', $appointment->end_date);
    }

    public function test_omitting_end_date_auto_computes_from_time_formatted_duration(): void
    {
        $service = Service::create([
            'name' => 'Full Color & Treatment',
            'service_category_id' => $this->category->id,
            'price' => 180.00,
            'duration' => 90, // 90 minutes
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 14:00:00',
            // end_date is omitted
        ]);

        $response->assertStatus(201);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame('2026-10-01 14:00:00', $appointment->start_date);
        $this->assertSame('2026-10-01 15:30:00', $appointment->end_date);
    }

    public function test_providing_explicit_end_date_overrides_service_duration(): void
    {
        $service = Service::create([
            'name' => 'Quick Trim',
            'service_category_id' => $this->category->id,
            'price' => 30.00,
            'duration' => 30, // 30 minutes catalog duration
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        // Explicitly book extended 2-hour consultation session
        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 12:00:00',
        ]);

        $response->assertStatus(201);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame('2026-10-01 10:00:00', $appointment->start_date);
        $this->assertSame('2026-10-01 12:00:00', $appointment->end_date);
    }

    public function test_updating_appointment_without_end_date_recalculates_from_service(): void
    {
        $service = Service::create([
            'name' => 'Manicure',
            'service_category_id' => $this->category->id,
            'price' => 40.00,
            'duration' => 60, // 60 minutes
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Update with new start date and omit end_date
        $response = $this->actingAs($this->admin)->putJson("/admin/appointments/{$appointment->id}", [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 16:00:00',
        ]);

        $response->assertOk();

        $appointment->refresh();
        $this->assertSame('2026-10-01 16:00:00', $appointment->start_date);
        $this->assertSame('2026-10-01 17:00:00', $appointment->end_date);
    }

    public function test_auto_computed_end_date_participates_in_double_booking_check(): void
    {
        $service = Service::create([
            'name' => 'Hair Coloring',
            'service_category_id' => $this->category->id,
            'price' => 120.00,
            'duration' => 60, // 60 minutes
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        // Booking 1: 10:00 without end_date (auto-computes to 11:00)
        $firstResponse = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
        ]);
        $firstResponse->assertStatus(201);

        // Booking 2: 10:30 without end_date (auto-computes to 11:30, overlaps with 10:00-11:00)
        $conflictResponse = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:30:00',
        ]);

        $conflictResponse->assertStatus(422);
        $conflictResponse->assertJsonValidationErrors(['provider_id']);
    }
}
