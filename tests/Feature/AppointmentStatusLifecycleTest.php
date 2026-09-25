<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentStatusLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Employee $provider;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Spatie permissions
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

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->provider = Employee::create([
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
            'phone' => '01099988877',
            'employee_level_id' => $level->id,
            'status' => 'active',
            'branch_id' => $branch->id,
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
            'branch_id' => $branch->id,
            'created_by' => $this->admin->id,
        ]);
    }

    private function createAppointment(string $status = 'requested'): Appointment
    {
        return Appointment::create([
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_appointment_created_has_default_requested_status(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/appointments', [
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'service_id' => $this->service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);

        $response->assertRedirect();

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame(AppointmentStatus::REQUESTED, $appointment->status);
        $this->assertTrue($appointment->isRequested());
    }

    public function test_appointment_can_transition_through_happy_path(): void
    {
        $appointment = $this->createAppointment('requested');

        // 1. Confirm: requested -> confirmed
        $confirmResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/confirm");
        $confirmResponse->assertOk();
        $this->assertSame(AppointmentStatus::CONFIRMED->value, $confirmResponse->json('data.status'));
        $appointment->refresh();
        $this->assertTrue($appointment->isConfirmed());

        // 2. Check-In: confirmed -> checked_in
        $checkInResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/check-in");
        $checkInResponse->assertOk();
        $this->assertSame(AppointmentStatus::CHECKED_IN->value, $checkInResponse->json('data.status'));
        $appointment->refresh();
        $this->assertTrue($appointment->isCheckedIn());

        // 3. Start Service: checked_in -> in_service
        $startServiceResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/start-service");
        $startServiceResponse->assertOk();
        $this->assertSame(AppointmentStatus::IN_SERVICE->value, $startServiceResponse->json('data.status'));
        $appointment->refresh();
        $this->assertTrue($appointment->isInService());

        // 4. Complete: in_service -> completed
        $completeResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/complete");
        $completeResponse->assertOk();
        $this->assertSame(AppointmentStatus::COMPLETED->value, $completeResponse->json('data.status'));
        $appointment->refresh();
        $this->assertTrue($appointment->isCompleted());
    }

    public function test_cancel_requires_reason_and_sets_cancelled_at(): void
    {
        $appointment = $this->createAppointment('confirmed');

        // Missing reason returns 422
        $failResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/cancel", [
            'cancellation_reason' => '',
        ]);
        $failResponse->assertStatus(422);
        $failResponse->assertJsonValidationErrors(['cancellation_reason']);

        // Valid cancellation
        $successResponse = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment->id}/cancel", [
            'cancellation_reason' => 'Customer requested cancellation due to personal emergency.',
        ]);

        $successResponse->assertOk();
        $this->assertSame(AppointmentStatus::CANCELLED->value, $successResponse->json('data.status'));

        $appointment->refresh();
        $this->assertTrue($appointment->isCancelled());
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertSame('Customer requested cancellation due to personal emergency.', $appointment->cancellation_reason);
    }

    public function test_no_show_transition_from_confirmed_and_checked_in(): void
    {
        // 1. From confirmed
        $appointment1 = $this->createAppointment('confirmed');
        $response1 = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment1->id}/no-show");
        $response1->assertOk();
        $appointment1->refresh();
        $this->assertTrue($appointment1->isNoShow());

        // 2. From checked_in
        $appointment2 = $this->createAppointment('checked_in');
        $response2 = $this->actingAs($this->admin)->postJson("/admin/appointments/{$appointment2->id}/no-show");
        $response2->assertOk();
        $appointment2->refresh();
        $this->assertTrue($appointment2->isNoShow());
    }

    public function test_invalid_transitions_return_422(): void
    {
        // Completed appointment cannot transition to confirmed
        $completedAppointment = $this->createAppointment('completed');
        $response = $this->actingAs($this->admin)->postJson("/admin/appointments/{$completedAppointment->id}/confirm");
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        // Cancelled appointment cannot transition to completed
        $cancelledAppointment = $this->createAppointment('cancelled');
        $response2 = $this->actingAs($this->admin)->postJson("/admin/appointments/{$cancelledAppointment->id}/complete");
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['status']);

        // Requested appointment cannot transition directly to completed
        $requestedAppointment = $this->createAppointment('requested');
        $response3 = $this->actingAs($this->admin)->postJson("/admin/appointments/{$requestedAppointment->id}/complete");
        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['status']);
    }

    public function test_unauthorized_user_cannot_perform_status_transitions(): void
    {
        $unauthorizedUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        // Give only appointments.index, but NOT appointments.edit
        $unauthorizedUser->givePermissionTo('appointments.index');

        $appointment = $this->createAppointment('requested');

        $response = $this->actingAs($unauthorizedUser)->postJson("/admin/appointments/{$appointment->id}/confirm");
        $response->assertStatus(403);
    }

    public function test_appointment_resource_includes_status_and_calendar_styling(): void
    {
        $appointment = $this->createAppointment('confirmed');

        $response = $this->actingAs($this->admin)->getJson('/admin/appointments');
        $response->assertOk();

        $item = collect($response->json())->firstWhere('id', $appointment->id);
        $this->assertNotNull($item);
        $this->assertSame('confirmed', $item['status']);
        $this->assertSame('Confirmed', $item['status_label']);
        $this->assertSame('#0d6efd', $item['backgroundColor']);
        $this->assertSame('#0d6efd', $item['borderColor']);
        $this->assertSame('#ffffff', $item['textColor']);
    }

    public function test_cannot_delete_confirmed_or_active_appointment(): void
    {
        $confirmed = $this->createAppointment('confirmed');

        $response = $this->actingAs($this->admin)->deleteJson("/admin/appointments/{$confirmed->id}");
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Only requested appointments can be deleted. Please cancel confirmed or active appointments instead.',
        ]);

        $this->assertDatabaseHas('appointments', ['id' => $confirmed->id]);

        // Requested appointments can be deleted
        $requested = $this->createAppointment('requested');
        $deleteRequestedResponse = $this->actingAs($this->admin)->deleteJson("/admin/appointments/{$requested->id}");
        $deleteRequestedResponse->assertStatus(200);
        $deleteRequestedResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('appointments', ['id' => $requested->id]);
    }

    public function test_rescheduled_appointment_can_transition_to_confirmed_or_cancelled(): void
    {
        $appointment = $this->createAppointment('confirmed');
        $appointment->transitionTo(AppointmentStatus::RESCHEDULED);

        $this->assertSame(AppointmentStatus::RESCHEDULED, $appointment->fresh()->status);

        // Can transition from RESCHEDULED to CONFIRMED
        $appointment->transitionTo(AppointmentStatus::CONFIRMED);
        $this->assertSame(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);

        // Can transition to RESCHEDULED again and then CANCELLED
        $appointment->transitionTo(AppointmentStatus::RESCHEDULED);
        $appointment->transitionTo(AppointmentStatus::CANCELLED, 'Client changed mind after reschedule');
        $this->assertSame(AppointmentStatus::CANCELLED, $appointment->fresh()->status);
        $this->assertSame('Client changed mind after reschedule', $appointment->fresh()->cancellation_reason);
    }
}
