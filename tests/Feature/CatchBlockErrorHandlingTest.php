<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLevel;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CatchBlockErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'employees.index',
            'employees.create',
            'employees.edit',
            'employees.show',
            'employees.destroy',
            'services.index',
            'services.create',
            'services.edit',
            'services.show',
            'services.destroy',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web'],
                ['group' => ucfirst(explode('.', $perm)[0])]
            );
        }

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin_catch_'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->user->givePermissionTo($permissions);

        $this->branch = Branch::create([
            'name' => 'HQ Branch',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_employee_store_exception_rolls_back_logs_and_redirects_with_input(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context = []) {
                return str_contains($message, 'Error creating employee')
                    && isset($context['exception']);
            });

        $level = EmployeeLevel::create([
            'name' => 'Stylist',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // We trigger an exception inside the transaction by hooking a model saving event
        Employee::saving(function () {
            throw new \RuntimeException('Simulated DB failure during employee creation');
        });

        $response = $this->actingAs($this->user)->post('/admin/employees', [
            'name' => 'John Crash',
            'email' => 'john.crash@example.com',
            'phone' => '0501234567',
            'employee_level_id' => $level->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'base_salary' => 5000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasInput('name', 'John Crash');

        // Verify transaction rolled back — no employee was inserted
        $this->assertDatabaseMissing('employees', [
            'email' => 'john.crash@example.com',
        ]);
    }

    public function test_service_store_exception_rolls_back_logs_and_redirects_with_input(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context = []) {
                return str_contains($message, 'Error creating service')
                    && isset($context['exception']);
            });

        $category = ServiceCategory::create([
            'name' => 'Spa',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        Service::saving(function () {
            throw new \RuntimeException('Simulated DB failure during service creation');
        });

        $response = $this->actingAs($this->user)->post('/admin/services', [
            'name' => 'Crash Facial',
            'price' => 120,
            'duration' => 60,
            'service_category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_target' => 0,
            'is_immediate_commission' => [1 => 1],
            'commission_type' => [1 => 'percentage'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasInput('name', 'Crash Facial');

        // Verify transaction rolled back — no service was inserted
        $this->assertDatabaseMissing('services', [
            'name' => 'Crash Facial',
        ]);
    }

    public function test_no_dd_calls_exist_in_application_code(): void
    {
        $appPath = app_path();
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appPath));

        $violatingFiles = [];
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());
                // Match active dd( calls
                if (preg_match('/^\s*dd\s*\(/m', $content) || preg_match('/[;{}]\s*dd\s*\(/', $content)) {
                    $violatingFiles[] = $file->getRealPath();
                }
            }
        }

        $this->assertEmpty($violatingFiles, 'Found active dd() calls in: '.implode(', ', $violatingFiles));
    }
}
