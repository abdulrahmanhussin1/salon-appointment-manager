<?php

namespace Database\Seeders\Development;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeWage;
use App\Models\Inventory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BranchAndStaffSeeder extends Seeder
{
    public function run(): void
    {
        $config = SimulationConfig::instance();

        // 1. Create 3 Branches
        $branchesData = [
            1 => [
                'name' => 'Downtown Flagship',
                'address' => '15 Tahrir Square, Downtown, Cairo',
                'phone' => '01011112222',
                'email' => 'downtown@luxeandglow.test',
            ],
            2 => [
                'name' => 'Uptown Mall Branch',
                'address' => 'Mall of Arabia, Gate 4, Giza',
                'phone' => '01033334444',
                'email' => 'mall@luxeandglow.test',
            ],
            3 => [
                'name' => 'Westside Boutique',
                'address' => '22 El-Gezira St, Zamalek, Cairo',
                'phone' => '01055556666',
                'email' => 'boutique@luxeandglow.test',
            ],
        ];

        foreach ($branchesData as $id => $data) {
            Branch::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'status' => 'active',
                    'created_by' => 1,
                ]
            );

            // Warehouse inventory for branch
            Inventory::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $data['name'].' Central Inventory',
                    'branch_id' => $id,
                    'status' => 'active',
                    'created_by' => 1,
                ]
            );
        }

        // 2. Define Employees across Branches
        $employeesList = [
            // --- Downtown Flagship (Branch 1) ---
            [
                'id' => 1,
                'name' => 'Karim Mansour',
                'job_title' => 'Branch Operations Manager',
                'branch_id' => 1,
                'level_id' => 3,
                'gender' => 'male',
                'hiring_date' => '2024-01-15',
                'salary' => 14000,
                'user_email' => 'manager.downtown@example.test',
                'role' => 'manager',
            ],
            [
                'id' => 2,
                'name' => 'Mona Zaki',
                'job_title' => 'Head Cashier',
                'branch_id' => 1,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-03-01',
                'salary' => 8500,
                'user_email' => 'cashier.downtown@example.test',
                'role' => 'cashier',
            ],
            [
                'id' => 3,
                'name' => 'Tarek Lotfy',
                'job_title' => 'Cashier',
                'branch_id' => 1,
                'level_id' => 1,
                'gender' => 'male',
                'hiring_date' => '2025-01-10',
                'salary' => 7000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 4,
                'name' => 'Yasmin Sabry',
                'job_title' => 'Lead Receptionist & Host',
                'branch_id' => 1,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-05-15',
                'salary' => 7500,
                'user_email' => 'receptionist@example.test',
                'role' => 'receptionist',
            ],
            [
                'id' => 5,
                'name' => 'Sara Ahmed',
                'job_title' => 'Master Stylist & Creative Director',
                'branch_id' => 1,
                'level_id' => 3,
                'gender' => 'female',
                'hiring_date' => '2023-11-01',
                'salary' => 16000,
                'user_email' => 'provider.sara@example.test',
                'role' => 'provider',
            ],
            [
                'id' => 6,
                'name' => 'Mohamed Nour',
                'job_title' => 'Senior Hair Stylist',
                'branch_id' => 1,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-02-15',
                'salary' => 11000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 7,
                'name' => 'Layla Youssef',
                'job_title' => 'Color & Balayage Specialist',
                'branch_id' => 1,
                'level_id' => 3,
                'gender' => 'female',
                'hiring_date' => '2024-04-01',
                'salary' => 13500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 8,
                'name' => 'Omar Sherif',
                'job_title' => 'Master Barber & Grooming Expert',
                'branch_id' => 1,
                'level_id' => 3,
                'gender' => 'male',
                'hiring_date' => '2024-06-01',
                'salary' => 10500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 9,
                'name' => 'Dina Fathy',
                'job_title' => 'Senior Nail Artist',
                'branch_id' => 1,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-08-10',
                'salary' => 9000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 10,
                'name' => 'Hoda Radwan',
                'job_title' => 'Senior Esthetician & Skincare Specialist',
                'branch_id' => 1,
                'level_id' => 4,
                'gender' => 'female',
                'hiring_date' => '2024-09-01',
                'salary' => 12500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 11,
                'name' => 'Ahmed Zaki',
                'job_title' => 'Licensed Massage & Spa Therapist',
                'branch_id' => 1,
                'level_id' => 4,
                'gender' => 'male',
                'hiring_date' => '2024-10-15',
                'salary' => 11500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 12,
                'name' => 'Nourhan Ezz',
                'job_title' => 'Junior Stylist & Color Assistant',
                'branch_id' => 1,
                'level_id' => 1,
                'gender' => 'female',
                'hiring_date' => '2025-02-01',
                'salary' => 6500,
                'user_email' => null,
                'role' => null,
            ],

            // --- Uptown Mall (Branch 2) ---
            [
                'id' => 13,
                'name' => 'Hany Adel',
                'job_title' => 'Mall Branch Manager',
                'branch_id' => 2,
                'level_id' => 3,
                'gender' => 'male',
                'hiring_date' => '2024-02-01',
                'salary' => 13000,
                'user_email' => 'manager.uptown@example.test',
                'role' => 'manager',
            ],
            [
                'id' => 14,
                'name' => 'Rania Youssef',
                'job_title' => 'Lead Cashier',
                'branch_id' => 2,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-04-10',
                'salary' => 8000,
                'user_email' => 'cashier.uptown@example.test',
                'role' => 'cashier',
            ],
            [
                'id' => 15,
                'name' => 'Sameh Samir',
                'job_title' => 'Cashier',
                'branch_id' => 2,
                'level_id' => 1,
                'gender' => 'male',
                'hiring_date' => '2025-03-01',
                'salary' => 6800,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 16,
                'name' => 'Salma Gamal',
                'job_title' => 'Receptionist',
                'branch_id' => 2,
                'level_id' => 1,
                'gender' => 'female',
                'hiring_date' => '2024-07-01',
                'salary' => 7000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 17,
                'name' => 'Mostafa Kamel',
                'job_title' => 'Senior Hair Stylist',
                'branch_id' => 2,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-03-15',
                'salary' => 11500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 18,
                'name' => 'Mariam Nabil',
                'job_title' => 'Nail Technician',
                'branch_id' => 2,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-05-20',
                'salary' => 8500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 19,
                'name' => 'Sherif Mounir',
                'job_title' => 'Barber & Stylist',
                'branch_id' => 2,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-08-01',
                'salary' => 9500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 20,
                'name' => 'Aya Hassan',
                'job_title' => 'Skincare Technician',
                'branch_id' => 2,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-09-15',
                'salary' => 9000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 21,
                'name' => 'Mahmoud Ehab',
                'job_title' => 'Junior Stylist',
                'branch_id' => 2,
                'level_id' => 1,
                'gender' => 'male',
                'hiring_date' => '2025-04-01',
                'salary' => 6500,
                'user_email' => null,
                'role' => null,
            ],

            // --- Westside Boutique (Branch 3) ---
            [
                'id' => 22,
                'name' => 'Noha Shaker',
                'job_title' => 'Boutique Manager',
                'branch_id' => 3,
                'level_id' => 3,
                'gender' => 'female',
                'hiring_date' => '2024-03-01',
                'salary' => 13500,
                'user_email' => 'manager.westside@example.test',
                'role' => 'manager',
            ],
            [
                'id' => 23,
                'name' => 'Amr Diab',
                'job_title' => 'Boutique Cashier',
                'branch_id' => 3,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-06-15',
                'salary' => 8000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 24,
                'name' => 'Reem Mostafa',
                'job_title' => 'Client Concierge & Receptionist',
                'branch_id' => 3,
                'level_id' => 2,
                'gender' => 'female',
                'hiring_date' => '2024-07-20',
                'salary' => 7500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 25,
                'name' => 'Farida Fahmy',
                'job_title' => 'Master Aesthetic Specialist',
                'branch_id' => 3,
                'level_id' => 4,
                'gender' => 'female',
                'hiring_date' => '2024-04-01',
                'salary' => 15000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 26,
                'name' => 'Ziad Rahmy',
                'job_title' => 'Senior Luxury Stylist',
                'branch_id' => 3,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-08-15',
                'salary' => 12000,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 27,
                'name' => 'Inas Naguib',
                'job_title' => 'Holistic Spa Therapist',
                'branch_id' => 3,
                'level_id' => 4,
                'gender' => 'female',
                'hiring_date' => '2024-11-01',
                'salary' => 11000,
                'user_email' => null,
                'role' => null,
            ],

            // --- Edge Cases: Terminated Staff & Late New Joiner ---
            [
                'id' => 28,
                'name' => 'Tamer Hosny',
                'job_title' => 'Stylist (Former)',
                'branch_id' => 1,
                'level_id' => 2,
                'gender' => 'male',
                'hiring_date' => '2024-01-01',
                'termination_date' => '2026-03-31',
                'status' => 'inactive',
                'inactive_reason' => 'Relocated abroad to Dubai',
                'salary' => 9500,
                'user_email' => null,
                'role' => null,
            ],
            [
                'id' => 29,
                'name' => 'Zeina El-Alamy',
                'job_title' => 'Junior Nail Technician',
                'branch_id' => 2,
                'level_id' => 1,
                'gender' => 'female',
                'hiring_date' => '2026-06-01',
                'salary' => 6000,
                'user_email' => null,
                'role' => null,
            ],
        ];

        foreach ($employeesList as $empData) {
            $nationalId = '29'.str_pad((string) $empData['id'], 6, '0', STR_PAD_LEFT).'123456';
            $phone = '01'.str_pad((string) $empData['id'], 9, '5', STR_PAD_LEFT);
            $email = 'emp.'.$empData['id'].'@luxeandglow.test';

            $employee = Employee::updateOrCreate(
                ['id' => $empData['id']],
                [
                    'name' => $empData['name'],
                    'job_title' => $empData['job_title'],
                    'branch_id' => $empData['branch_id'],
                    'employee_level_id' => $empData['level_id'],
                    'gender' => $empData['gender'],
                    'hiring_date' => $empData['hiring_date'],
                    'termination_date' => $empData['termination_date'] ?? null,
                    'status' => $empData['status'] ?? 'active',
                    'inactive_reason' => $empData['inactive_reason'] ?? null,
                    'national_id' => $nationalId,
                    'phone' => $phone,
                    'email' => $email,
                    'created_by' => 1,
                ]
            );

            // Wage configuration
            EmployeeWage::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'salary_type' => 'monthly',
                    'basic_salary' => $empData['salary'],
                    'bonus_salary' => 500,
                    'total_salary' => $empData['salary'] + 500,
                    'working_hours' => 8,
                    'start_working_time' => '10:00:00',
                    'overtime_rate' => 1.5,
                    'penalty_late_hour' => 50,
                    'penalty_absence_day' => 250,
                    'sales_target_settings' => 'total_sales',
                    'break_duration_minutes' => 60,
                ]
            );

            // Create login user account if defined
            if (! empty($empData['user_email'])) {
                $user = User::updateOrCreate(
                    ['email' => $empData['user_email']],
                    [
                        'name' => $empData['name'],
                        'password' => Hash::make('password'),
                        'employee_id' => $employee->id,
                        'status' => 'active',
                        'created_by' => 1,
                    ]
                );

                if (! empty($empData['role'])) {
                    $roleModel = Role::where('name', $empData['role'])->first();
                    if ($roleModel) {
                        $user->syncRoles([$roleModel]);
                    }
                }
            }
        }

        // 3. Link Branch Managers
        Branch::where('id', 1)->update(['manager_id' => 1]); // Karim Mansour
        Branch::where('id', 2)->update(['manager_id' => 13]); // Hany Adel
        Branch::where('id', 3)->update(['manager_id' => 22]); // Noha Shaker
    }
}
