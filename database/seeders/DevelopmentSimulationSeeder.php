<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SalesInvoice;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\Development\AppointmentAndSalesSimulator;
use Database\Seeders\Development\BranchAndStaffSeeder;
use Database\Seeders\Development\CatalogSeeder;
use Database\Seeders\Development\CurrentDaySimulator;
use Database\Seeders\Development\CustomerSeeder;
use Database\Seeders\Development\ExpenseSimulator;
use Database\Seeders\Development\FoundationSeeder;
use Database\Seeders\Development\InventorySeeder;
use Database\Seeders\Development\SimulationConfig;
use Database\Seeders\Development\SimulationValidator;
use Illuminate\Database\Seeder;

class DevelopmentSimulationSeeder extends Seeder
{
    /**
     * Run the complete 1-Year Development Simulation.
     */
    public function run(?string $start = null, ?string $end = null, ?int $seed = null): void
    {
        $startTime = microtime(true);
        $config = SimulationConfig::instance($start, $end, $seed);

        $this->command?->info("🚀 Starting 1-Year Realistic Business Simulation...");
        $this->command?->info("📅 Period: {$config->startDate->toDateString()} to {$config->endDate->toDateString()} (Anchor: {$config->anchorDate->toDateString()})");
        $this->command?->info("🎲 Seed: {$config->seed}");

        // Phase 1: Foundation
        $this->command?->info("Phase 1/8: Seeding Foundation & Settings...");
        $this->call(FoundationSeeder::class);

        // Phase 2: Branches & Staff
        $this->command?->info("Phase 2/8: Seeding Branches, Staff & Role Accounts...");
        $this->call(BranchAndStaffSeeder::class);

        // Phase 3: Catalog
        $this->command?->info("Phase 3/8: Seeding Services, Products & Consumable Links...");
        $this->call(CatalogSeeder::class);

        // Phase 4: Inventory
        $this->command?->info("Phase 4/8: Simulating Supplier Purchases & FIFO Cost Lots...");
        $this->call(InventorySeeder::class);

        // Phase 5: Customers
        $this->command?->info("Phase 5/8: Seeding Segmented Customers & VIP Deposits...");
        $this->call(CustomerSeeder::class);

        // Phase 6: Expenses
        $this->command?->info("Phase 6/8: Simulating 12-Month Operating Expenses & Overhead...");
        $this->call(ExpenseSimulator::class);

        // Phase 7: Historical Activity (Appointments, POS Sales, Commissions, Refunds)
        $this->command?->info("Phase 7/8: Simulating Appointments, Sales, Commissions & Returns...");
        $this->call(AppointmentAndSalesSimulator::class);

        // Phase 8: Current Day Activity & Future Horizon
        $this->command?->info("Phase 8/8: Crafting Today's Live Dashboard State & Near-Future Bookings...");
        $this->call(CurrentDaySimulator::class);

        $duration = round(microtime(true) - $startTime, 2);

        // Run Validator
        $this->command?->info("🔍 Running Simulation Data Integrity Validator...");
        $validator = new SimulationValidator();
        $report = $validator->validate();

        $this->command?->info("---------------------------------------------------------------");
        $this->command?->info("🎉 1-Year Business Simulation Complete in {$duration} seconds!");
        $this->command?->info("---------------------------------------------------------------");

        // Display Record Counts
        if ($this->command) {
            $this->command->table(
                ['Entity', 'Count'],
                [
                    ['Branches', Branch::count()],
                    ['Users (System Accounts)', User::count()],
                    ['Staff / Employees', Employee::count()],
                    ['Customers', Customer::count()],
                    ['Services Catalog', Service::count()],
                    ['Products Catalog', Product::count()],
                    ['Appointments (Historical + Future)', Appointment::count()],
                    ['Sales Invoices (POS)', SalesInvoice::count()],
                    ['Operating Expenses', Expense::count()],
                    ['Refunds & Returns', Refund::count()],
                ]
            );

            // Display Validation Summary
            $validationRows = [];
            foreach ($report as $key => $check) {
                if ($key === 'all_passed') {
                    continue;
                }
                $validationRows[] = [
                    $check['name'],
                    $check['passed'] ? '✅ PASS' : '❌ FAIL',
                ];
            }
            $this->command->table(['Validation Check', 'Result'], $validationRows);

            if (! $report['all_passed']) {
                $this->command->error("⚠️ Some data integrity checks failed. Review detailed report.");
            } else {
                $this->command->info("✅ All data integrity assertions passed with 100% compliance.");
            }
        }
    }
}
