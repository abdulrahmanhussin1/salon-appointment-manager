<?php

namespace Database\Seeders\Development;

use App\Models\AdminPanelSetting;
use App\Models\EmployeeLevel;
use App\Models\ExpenseType;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        // 1. Root Admin User (ID 1)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin Owner',
                'password' => Hash::make('password'),
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        // Ensure legacy admin email is also updated or alias available
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Legacy Admin',
                'password' => Hash::make('123456789'),
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        // 2. Admin Panel Settings
        AdminPanelSetting::updateOrCreate(
            ['id' => 1],
            [
                'system_name' => 'Luxe & Glow Salon & Spa',
                'system_phone' => '0227361840',
                'system_address' => '15 Tahrir Square, Downtown, Cairo, Egypt',
                'system_notes' => 'Premier luxury beauty salon and wellness spa management platform.',
                'system_logo' => 'admin-assets/assets/img/next-logo.jpeg',
                'void_time_window_hours' => 24,
                'block_insufficient_consumables' => false,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
            ]
        );

        // 3. Spatie Permissions & Roles
        $allPermissions = [
            'admin_panel_settings.index', 'admin_panel_settings.update',
            'roles.index', 'roles.show', 'roles.create', 'roles.edit', 'roles.rolesPermissions', 'roles.destroy',
            'users.index', 'users.show', 'users.create', 'users.edit', 'users.destroy',
            'units.index', 'units.show', 'units.create', 'units.edit', 'units.destroy',
            'suppliers.index', 'suppliers.show', 'suppliers.create', 'suppliers.edit', 'suppliers.destroy',
            'product_categories.index', 'product_categories.show', 'product_categories.create', 'product_categories.edit', 'product_categories.destroy',
            'products.index', 'products.show', 'products.create', 'products.edit', 'products.destroy',
            'employee_levels.index', 'employee_levels.show', 'employee_levels.create', 'employee_levels.edit', 'employee_levels.destroy',
            'employees.index', 'employees.show', 'employees.create', 'employees.edit', 'employees.destroy',
            'tools.index', 'tools.show', 'tools.create', 'tools.edit', 'tools.destroy',
            'service_categories.index', 'service_categories.show', 'service_categories.create', 'service_categories.edit', 'service_categories.destroy',
            'services.index', 'services.show', 'services.create', 'services.edit', 'services.destroy',
            'customers.index', 'customers.show', 'customers.create', 'customers.edit', 'customers.destroy',
            'branches.index', 'branches.show', 'branches.create', 'branches.edit', 'branches.destroy',
            'expense_types.index', 'expense_types.show', 'expense_types.create', 'expense_types.edit', 'expense_types.destroy',
            'expenses.index', 'expenses.show', 'expenses.create', 'expenses.edit', 'expenses.destroy',
            'payment_methods.index', 'payment_methods.show', 'payment_methods.create', 'payment_methods.edit', 'payment_methods.destroy',
            'purchase_invoices.index', 'purchase_invoices.show', 'purchase_invoices.create', 'purchase_invoices.edit', 'purchase_invoices.destroy',
            'sales_invoices.index', 'sales_invoices.show', 'sales_invoices.create', 'sales_invoices.edit', 'sales_invoices.destroy', 'sales_invoices.void',
            'refunds.index', 'refunds.show', 'refunds.create',
            'inventories.index', 'inventories.show', 'inventories.create', 'inventories.edit', 'inventories.destroy',
            'inventory_transactions.transferView', 'inventory_transactions.transferOutView', 'inventory_transactions.adjustView',
            'customer_transactions.get_customer_payments', 'customer_transactions.store_customer_payment',
            'reports.index', 'reports.branch_revenue',
            'appointments.index', 'appointments.show', 'appointments.create', 'appointments.edit', 'appointments.destroy',
        ];

        foreach ($allPermissions as $permName) {
            $group = ucfirst(explode('.', str_replace('_', ' ', $permName))[0]);
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => $guardName],
                ['group' => $group, 'status' => 'active']
            );
        }

        // Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => $guardName],
            ['description' => 'System Owner and Administrator', 'status' => 'active', 'created_by' => 1]
        );
        $adminRole->syncPermissions(Permission::all());
        $admin->syncRoles([$adminRole]);

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => $guardName],
            ['description' => 'Branch Operations Manager', 'status' => 'active', 'created_by' => 1]
        );
        $managerRole->syncPermissions(Permission::whereNotIn('name', [
            'admin_panel_settings.update', 'roles.destroy', 'users.destroy',
        ])->get());

        $cashierRole = Role::firstOrCreate(
            ['name' => 'cashier', 'guard_name' => $guardName],
            ['description' => 'POS Cashier & Checkout Operator', 'status' => 'active', 'created_by' => 1]
        );
        $cashierRole->syncPermissions(Permission::whereIn('name', [
            'sales_invoices.index', 'sales_invoices.show', 'sales_invoices.create', 'sales_invoices.void',
            'refunds.index', 'refunds.show', 'refunds.create',
            'customers.index', 'customers.show', 'customers.create', 'customers.edit',
            'appointments.index', 'appointments.show', 'appointments.create', 'appointments.edit',
            'customer_transactions.get_customer_payments', 'customer_transactions.store_customer_payment',
            'expenses.index', 'expenses.create',
            'products.index', 'services.index',
        ])->get());

        $receptionistRole = Role::firstOrCreate(
            ['name' => 'receptionist', 'guard_name' => $guardName],
            ['description' => 'Front Desk & Appointment Coordinator', 'status' => 'active', 'created_by' => 1]
        );
        $receptionistRole->syncPermissions(Permission::whereIn('name', [
            'appointments.index', 'appointments.show', 'appointments.create', 'appointments.edit',
            'customers.index', 'customers.show', 'customers.create', 'customers.edit',
            'services.index', 'employees.index',
        ])->get());

        $providerRole = Role::firstOrCreate(
            ['name' => 'provider', 'guard_name' => $guardName],
            ['description' => 'Service Staff / Stylist / Specialist', 'status' => 'active', 'created_by' => 1]
        );
        $providerRole->syncPermissions(Permission::whereIn('name', [
            'appointments.index', 'appointments.show',
        ])->get());

        // 4. Payment Methods
        $paymentMethods = [
            ['id' => 1, 'name' => 'cash', 'description' => 'Cash in Hand (EGP)'],
            ['id' => 2, 'name' => 'credit card', 'description' => 'Visa / Mastercard / Meeza Card Terminal'],
            ['id' => 3, 'name' => 'bank transfer', 'description' => 'Direct Bank Wire / InstaPay Electronic Transfer'],
        ];
        foreach ($paymentMethods as $pm) {
            PaymentMethod::updateOrCreate(
                ['id' => $pm['id']],
                [
                    'name' => $pm['name'],
                    'description' => $pm['description'],
                    'status' => 'active',
                    'created_by' => 1,
                ]
            );
        }

        // 5. Expense Types
        $expenseTypes = [
            1 => 'Rent & Facilities Lease',
            2 => 'Utilities (Electricity & Water)',
            3 => 'Professional Salon Supplies & Consumables',
            4 => 'Equipment Maintenance & Repairs',
            5 => 'Marketing & Social Media Advertising',
            6 => 'Staff Refreshments & Hospitality',
            7 => 'Software & POS Subscriptions',
            8 => 'Cleaning & Sanitation Services',
        ];
        foreach ($expenseTypes as $id => $name) {
            ExpenseType::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 6. Units
        $units = [
            1 => 'Piece',
            2 => 'Bottle',
            3 => 'Box',
            4 => 'ml',
            5 => 'g',
            6 => 'Kit',
        ];
        foreach ($units as $id => $name) {
            Unit::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 7. Tools
        $tools = [
            1 => 'Professional Ionic Hairdryer',
            2 => 'Japanese Steel Styling Shears',
            3 => 'Ceramic Straightening & Curling Iron',
            4 => 'UV/LED Gel Nail Curing Lamp',
            5 => 'Facial Ozone Steamer Unit',
            6 => 'Medical-Grade Autoclave Sterilizer',
            7 => 'Cordless Precision Clipper & Trimmer',
            8 => 'Heated Basalt Stone Massage Warmer',
        ];
        foreach ($tools as $id => $name) {
            Tool::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 8. Employee Levels
        $levels = [
            1 => 'Junior Stylist / Apprentice',
            2 => 'Senior Stylist / Technician',
            3 => 'Master Stylist / Department Lead',
            4 => 'Spa Therapist & Aesthetic Specialist',
        ];
        foreach ($levels as $id => $name) {
            EmployeeLevel::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 9. Service Categories
        $serviceCategories = [
            1 => 'Haircuts & Styling',
            2 => 'Hair Color & Balayage Treatments',
            3 => 'Nail Care & Aesthetics',
            4 => 'Facial & Skincare Treatments',
            5 => 'Spa & Therapeutic Massages',
            6 => "Men's Grooming & Beard Care",
        ];
        foreach ($serviceCategories as $id => $name) {
            ServiceCategory::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 10. Product Categories
        $productCategories = [
            1 => 'Professional Hair Care (Shampoos & Conditioners)',
            2 => 'Styling Sprays, Serums & Oils',
            3 => 'Skincare Serums & Moisturizers',
            4 => 'Nail Lacquers & Care Kits',
            5 => 'Backbar Professional Consumables',
        ];
        foreach ($productCategories as $id => $name) {
            ProductCategory::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'status' => 'active', 'created_by' => 1]
            );
        }

        // 11. Professional Suppliers
        $suppliers = [
            1 => ['name' => "L'Oréal Professionnel Egypt", 'email' => 'orders@loreal-eg.test', 'phone' => '0225890001', 'address' => 'Plot 44, New Cairo Industrial Zone'],
            2 => ['name' => 'Schwarzkopf Professional ME', 'email' => 'sales@schwarzkopf-me.test', 'phone' => '0225890002', 'address' => 'Nasr City Logistics Hub, Cairo'],
            3 => ['name' => 'Kérastase Paris Middle East', 'email' => 'supply@kerastase-me.test', 'phone' => '0225890003', 'address' => 'Smart Village, Building B12, Giza'],
            4 => ['name' => 'OPI & Essie Nail Distribution', 'email' => 'orders@opinails-eg.test', 'phone' => '0225890004', 'address' => '12 Mohandessin Commercial St, Giza'],
            5 => ['name' => 'Dermalogica Skincare ME', 'email' => 'distro@dermalogica-me.test', 'phone' => '0225890005', 'address' => 'Maadi Tech Park, Cairo'],
            6 => ['name' => 'Al-Amal Salon Equipment & Furniture', 'email' => 'info@alamal-salon.test', 'phone' => '0225890006', 'address' => 'Al-Azhar Industrial Ave, Cairo'],
        ];
        foreach ($suppliers as $id => $sup) {
            Supplier::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $sup['name'],
                    'email' => $sup['email'],
                    'phone' => $sup['phone'],
                    'address' => $sup['address'],
                    'status' => 'active',
                    'created_by' => 1,
                ]
            );
        }
    }
}
