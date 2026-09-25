<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
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

class AppointmentDoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Employee $providerA;

    private Employee $providerB;

    private Service $service;

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

        $this->providerA = Employee::create([
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
            'phone' => '01099988877',
            'employee_level_id' => $level->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->providerB = Employee::create([
            'name' => 'Marco Rossi',
            'email' => 'marco@example.com',
            'phone' => '01055544433',
            'employee_level_id' => $level->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Care',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->service = Service::create([
            'name' => 'Balayage & Styling',
            'service_category_id' => $category->id,
            'price' => 150.00,
            'duration' => '01:00:00',
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_cannot_create_overlapping_appointment_for_same_provider(): void
    {
        // Existing confirmed appointment 10:00 - 11:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Attempt overlapping: 10:30 - 11:30
        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:30:00',
            'end_date' => '2026-10-01 11:30:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider_id']);

        // Attempt enclosing: 09:30 - 11:30
        $enclosingResponse = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 09:30:00',
            'end_date' => '2026-10-01 11:30:00',
        ]);
        $enclosingResponse->assertStatus(422);
        $enclosingResponse->assertJsonValidationErrors(['provider_id']);
    }

    public function test_can_create_consecutive_appointments_for_same_provider(): void
    {
        // Existing confirmed appointment: 10:00 - 11:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // 1. Immediately after: 11:00 - 12:00
        $afterResponse = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 11:00:00',
            'end_date' => '2026-10-01 12:00:00',
        ]);
        $afterResponse->assertStatus(201);

        // 2. Immediately before: 09:00 - 10:00
        $beforeResponse = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 09:00:00',
            'end_date' => '2026-10-01 10:00:00',
        ]);
        $beforeResponse->assertStatus(201);
    }

    public function test_can_create_overlapping_appointment_for_different_provider(): void
    {
        // Existing appointment for Provider A: 10:00 - 11:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Same time slot for Provider B: 10:00 - 11:00
        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerB->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_cancelled_or_terminal_appointments_do_not_block_booking(): void
    {
        // Existing cancelled appointment: 10:00 - 11:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CANCELLED->value,
            'cancellation_reason' => 'Customer called to cancel',
            'created_by' => $this->admin->id,
        ]);

        // Same time slot for Provider A succeeds because existing is cancelled
        $response = $this->actingAs($this->admin)->postJson('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_updating_appointment_can_keep_same_time_slot_without_self_conflict(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Update with same start and end date
        $response = $this->actingAs($this->admin)->putJson("/admin/appointments/{$appointment->id}", [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);

        $response->assertOk();
    }

    public function test_updating_appointment_to_overlap_with_another_appointment_fails(): void
    {
        // Appointment 1: 10:00 - 11:00
        $appointment1 = Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Appointment 2: 14:00 - 15:00
        $appointment2 = Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 14:00:00',
            'end_date' => '2026-10-01 15:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Attempt to move Appointment 2 to 10:30 - 11:30 (conflicts with Appointment 1)
        $response = $this->actingAs($this->admin)->putJson("/admin/appointments/{$appointment2->id}", [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:30:00',
            'end_date' => '2026-10-01 11:30:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider_id']);
    }

    public function test_cannot_confirm_requested_appointment_if_overlapping_confirmed_exists(): void
    {
        // Confirmed appointment 10:00 - 11:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => AppointmentStatus::CONFIRMED->value,
            'created_by' => $this->admin->id,
        ]);

        // Requested appointment directly created at 10:15 - 10:45
        $requestedAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:15:00',
            'end_date' => '2026-10-01 10:45:00',
            'status' => AppointmentStatus::REQUESTED->value,
            'created_by' => $this->admin->id,
        ]);

        // Attempt to confirm the requested appointment
        $response = $this->actingAs($this->admin)->postJson("/admin/appointments/{$requestedAppointment->id}/confirm");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider_id']);
    }
}
