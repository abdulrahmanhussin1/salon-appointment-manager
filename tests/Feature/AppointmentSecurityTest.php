<?php

namespace Tests\Feature;

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

class AppointmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic permissions
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
    }

    private function createAdminUser(): User
    {
        $creator = User::create([
            'name' => 'System Admin',
            'email' => 'admin_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);

        return $creator;
    }

    public function test_unauthenticated_user_cannot_access_appointments(): void
    {
        // GET /admin/appointments must redirect to login
        $response = $this->get('/admin/appointments');
        $response->assertRedirect('/admin/login');

        // Legacy GET /appointments must redirect
        $legacyResponse = $this->get('/appointments');
        $legacyResponse->assertRedirect();
    }

    public function test_unauthenticated_user_cannot_store_appointment(): void
    {
        $response = $this->post('/admin/appointments', [
            'customer_id' => 1,
            'provider_id' => 1,
            'service_id' => 1,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);

        $response->assertRedirect('/admin/login');

        // JSON unauthenticated request returns 401
        $jsonResponse = $this->postJson('/admin/appointments', [
            'customer_id' => 1,
        ]);
        $jsonResponse->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_calendar(): void
    {
        $response = $this->get('/admin/calender');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_permission_is_denied(): void
    {
        $user = $this->createAdminUser();

        // User has no permissions attached
        $response = $this->actingAs($user)->get('/admin/appointments');
        $response->assertStatus(403);

        $createResponse = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => 1,
            'provider_id' => 1,
            'service_id' => 1,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 11:00:00',
        ]);
        $createResponse->assertStatus(403);

        $calendarResponse = $this->actingAs($user)->get('/admin/calender');
        $calendarResponse->assertStatus(403);
    }

    public function test_store_validates_required_fields_and_foreign_keys(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.create');

        // Empty payload
        $response = $this->actingAs($user)->post('/admin/appointments', []);
        $response->assertSessionHasErrors([
            'customer_id',
            'provider_id',
            'service_id',
            'start_date',
        ]);

        // Invalid foreign keys and invalid date ordering
        $invalidResponse = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => 99999,
            'provider_id' => 99999,
            'service_id' => 99999,
            'start_date' => '2026-10-01 12:00:00',
            'end_date' => '2026-10-01 10:00:00', // end_date before start_date
        ]);

        $invalidResponse->assertSessionHasErrors([
            'customer_id',
            'provider_id',
            'service_id',
            'end_date',
        ]);
    }

    public function test_authenticated_authorized_user_can_create_appointment(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.create');

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $employee = Employee::create([
            'name' => 'Alice Stylist',
            'email' => 'alice@example.com',
            'phone' => '0987654321',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 10:30:00',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_update_requires_permission_and_validates_input(): void
    {
        $user = $this->createAdminUser();

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $employee = Employee::create([
            'name' => 'Alice Stylist',
            'email' => 'alice@example.com',
            'phone' => '0987654321',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 10:30:00',
            'created_by' => $user->id,
        ]);

        // Unauthenticated update
        $unauth = $this->put("/admin/appointments/{$appointment->id}", [
            'customer_id' => $customer->id,
        ]);
        $unauth->assertRedirect('/admin/login');

        // Without appointments.edit permission
        $forbidden = $this->actingAs($user)->put("/admin/appointments/{$appointment->id}", [
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 11:00:00',
            'end_date' => '2026-10-01 11:30:00',
        ]);
        $forbidden->assertStatus(403);

        // With appointments.edit permission, invalid inputs
        $user->givePermissionTo('appointments.edit');
        $invalid = $this->actingAs($user)->put("/admin/appointments/{$appointment->id}", [
            'customer_id' => 99999,
        ]);
        $invalid->assertSessionHasErrors(['customer_id', 'provider_id', 'service_id', 'start_date']);

        // With appointments.edit permission, valid inputs
        $success = $this->actingAs($user)->put("/admin/appointments/{$appointment->id}", [
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 11:00:00',
            'end_date' => '2026-10-01 11:30:00',
        ]);
        $success->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_date' => '2026-10-01 11:00:00',
            'end_date' => '2026-10-01 11:30:00',
            'updated_by' => $user->id,
        ]);
    }

    public function test_update_uses_route_parameter_and_ignores_id_in_request_body(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.edit');

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $employee = Employee::create([
            'name' => 'Alice Stylist',
            'email' => 'alice@example.com',
            'phone' => '0987654321',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $appointmentA = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 10:30:00',
            'created_by' => $user->id,
        ]);

        $appointmentB = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-02 14:00:00',
            'end_date' => '2026-10-02 14:30:00',
            'created_by' => $user->id,
        ]);

        // Attempt to update appointment A, but pass appointment B's ID in the body
        $response = $this->actingAs($user)->put("/admin/appointments/{$appointmentA->id}", [
            'id' => $appointmentB->id,
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 15:00:00',
            'end_date' => '2026-10-01 15:30:00',
        ]);

        $response->assertRedirect();

        // Appointment A MUST be updated
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentA->id,
            'start_date' => '2026-10-01 15:00:00',
            'end_date' => '2026-10-01 15:30:00',
        ]);

        // Appointment B MUST NOT be touched
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentB->id,
            'start_date' => '2026-10-02 14:00:00',
            'end_date' => '2026-10-02 14:30:00',
        ]);
    }

    public function test_destroy_uses_route_parameter_and_ignores_id_in_request_body(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.destroy');

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $employee = Employee::create([
            'name' => 'Alice Stylist',
            'email' => 'alice@example.com',
            'phone' => '0987654321',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $appointmentA = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 10:30:00',
            'created_by' => $user->id,
        ]);

        $appointmentB = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-02 14:00:00',
            'end_date' => '2026-10-02 14:30:00',
            'created_by' => $user->id,
        ]);

        // Attempt to delete appointment A, but pass appointment B's ID in the body
        $response = $this->actingAs($user)->delete("/admin/appointments/{$appointmentA->id}", [
            'id' => $appointmentB->id,
        ]);

        $response->assertRedirect();

        // Appointment A MUST be deleted
        $this->assertDatabaseMissing('appointments', [
            'id' => $appointmentA->id,
        ]);

        // Appointment B MUST still exist
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentB->id,
        ]);
    }

    public function test_update_and_destroy_return_404_when_route_id_does_not_exist(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.edit');
        $user->givePermissionTo('appointments.destroy');

        $customer = Customer::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $employee = Employee::create([
            'name' => 'Alice Stylist',
            'email' => 'alice@example.com',
            'phone' => '0987654321',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Update on non-existent route ID
        $updateResponse = $this->actingAs($user)->put('/admin/appointments/99999', [
            'customer_id' => $customer->id,
            'provider_id' => $employee->id,
            'service_id' => $service->id,
            'start_date' => '2026-10-01 10:00:00',
            'end_date' => '2026-10-01 10:30:00',
        ]);
        $updateResponse->assertNotFound();

        // Destroy on non-existent route ID
        $deleteResponse = $this->actingAs($user)->delete('/admin/appointments/99999');
        $deleteResponse->assertNotFound();
    }

    public function test_store_rejects_inactive_customer_provider_and_service(): void
    {
        $user = $this->createAdminUser();
        $user->givePermissionTo('appointments.create');

        $branch = \App\Models\Branch::create([
            'name' => 'Main Branch',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $activeCustomer = Customer::create([
            'name' => 'Active Customer',
            'email' => 'active@example.com',
            'phone' => '1111111111',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $inactiveCustomer = Customer::create([
            'name' => 'Inactive Customer',
            'email' => 'inactive@example.com',
            'phone' => '2222222222',
            'status' => 'inactive',
            'created_by' => $user->id,
        ]);

        $level = EmployeeLevel::create([
            'name' => 'Senior Stylist',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $activeProvider = Employee::create([
            'name' => 'Active Provider',
            'email' => 'actprov@example.com',
            'phone' => '3333333333',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $inactiveProvider = Employee::create([
            'name' => 'Inactive Provider',
            'email' => 'inactprov@example.com',
            'phone' => '4444444444',
            'employee_level_id' => $level->id,
            'branch_id' => $branch->id,
            'status' => 'inactive',
            'created_by' => $user->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Hair Services',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $activeService = Service::create([
            'name' => 'Active Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $inactiveService = Service::create([
            'name' => 'Inactive Haircut',
            'price' => 50.00,
            'duration' => 30,
            'service_category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'inactive',
            'created_by' => $user->id,
        ]);

        // Inactive customer fails
        $response1 = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => $inactiveCustomer->id,
            'provider_id' => $activeProvider->id,
            'service_id' => $activeService->id,
            'start_date' => '2026-10-01 10:00:00',
        ]);
        $response1->assertSessionHasErrors(['customer_id']);

        // Inactive provider fails
        $response2 = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => $activeCustomer->id,
            'provider_id' => $inactiveProvider->id,
            'service_id' => $activeService->id,
            'start_date' => '2026-10-01 10:00:00',
        ]);
        $response2->assertSessionHasErrors(['provider_id']);

        // Inactive service fails
        $response3 = $this->actingAs($user)->post('/admin/appointments', [
            'customer_id' => $activeCustomer->id,
            'provider_id' => $activeProvider->id,
            'service_id' => $inactiveService->id,
            'start_date' => '2026-10-01 10:00:00',
        ]);
        $response3->assertSessionHasErrors(['service_id']);
    }
}
