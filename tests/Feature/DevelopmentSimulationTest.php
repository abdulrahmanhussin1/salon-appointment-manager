<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SalesInvoice;
use App\Models\Service;
use Database\Seeders\Development\SimulationConfig;
use Database\Seeders\Development\SimulationValidator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevelopmentSimulationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql']);
        DB::purge('sqlite');
    }

    /**
     * Test that SimulationValidator passes all business and data integrity rules.
     */
    public function test_simulation_validator_passes_all_checks(): void
    {
        $validator = new SimulationValidator();
        $results = $validator->validate();

        $this->assertTrue(
            $results['all_passed'],
            'Simulation validator failed: ' . json_encode($results, JSON_PRETTY_PRINT)
        );
        $this->assertTrue($results['relationships']['passed']);
        $this->assertTrue($results['financial']['passed']);
        $this->assertTrue($results['inventory']['passed']);
        $this->assertTrue($results['appointments']['passed']);
        $this->assertTrue($results['commissions']['passed']);
        $this->assertTrue($results['authorization']['passed']);
    }

    /**
     * Test that simulated entity volumes meet real operating business scale.
     */
    public function test_record_counts_meet_business_scale_expectations(): void
    {
        $this->assertGreaterThanOrEqual(3, Branch::count(), 'Expected at least 3 branches');
        $this->assertGreaterThanOrEqual(25, Employee::count(), 'Expected at least 25 employees');
        $this->assertGreaterThanOrEqual(1000, Customer::count(), 'Expected at least 1,000 customers');
        $this->assertGreaterThanOrEqual(30, Service::count(), 'Expected at least 30 services');
        $this->assertGreaterThanOrEqual(30, Product::count(), 'Expected at least 30 products');
        $this->assertGreaterThanOrEqual(2500, Appointment::count(), 'Expected at least 2,500 appointments');
        $this->assertGreaterThanOrEqual(2000, SalesInvoice::count(), 'Expected at least 2,000 sales invoices');
        $this->assertGreaterThanOrEqual(150, Expense::count(), 'Expected at least 150 expense records');
        $this->assertGreaterThanOrEqual(25, Refund::count(), 'Expected at least 25 refunds');
    }

    /**
     * Test financial accounting equations across all sales invoices.
     */
    public function test_financial_integrity_and_accounting_equations(): void
    {
        $invoices = SalesInvoice::with('salesInvoiceDetails')->limit(200)->get();

        foreach ($invoices as $inv) {
            $lineGross = round((float) $inv->salesInvoiceDetails->sum(fn ($d) => (float) $d->customer_price * (float) $d->quantity), 2);
            $this->assertEqualsWithDelta(
                (float) $inv->total_amount,
                $lineGross,
                0.05,
                "Invoice #{$inv->id} total_amount should match gross line items"
            );

            $lineNet = round((float) $inv->salesInvoiceDetails->sum('subtotal'), 2);
            $this->assertEqualsWithDelta(
                (float) $inv->net_total,
                $lineNet,
                0.05,
                "Invoice #{$inv->id} net_total should match sum of detail subtotals"
            );

            $computedNet = round((float) $inv->total_amount - (float) $inv->invoice_discount + (float) $inv->invoice_tax, 2);
            $this->assertEqualsWithDelta(
                (float) $inv->net_total,
                $computedNet,
                0.05,
                "Invoice #{$inv->id} net_total must reconcile with total - discount + tax"
            );

            $computedBalance = round((float) $inv->net_total - (float) $inv->paid_amount_cash - (float) $inv->payment_method_value - (float) $inv->invoice_deposit, 2);
            $this->assertEqualsWithDelta(
                (float) $inv->balance_due,
                $computedBalance,
                0.05,
                "Invoice #{$inv->id} balance_due must equal net_total minus payments"
            );
        }
    }

    /**
     * Test that inventory quantities are valid and never negative.
     */
    public function test_inventory_has_no_negative_stock_balances(): void
    {
        $negativeCount = InventoryProduct::where('quantity', '<', 0)->count();
        $this->assertSame(0, $negativeCount, 'No inventory product should have negative stock');

        // Verify out-of-stock and low-stock products exist as realistic business states
        $zeroStockCount = InventoryProduct::where('quantity', 0)->count();
        $this->assertGreaterThan(0, $zeroStockCount, 'Should have controlled out-of-stock items for alerts');
    }

    /**
     * Test appointment lifecycle statuses and operational consistency.
     */
    public function test_appointment_lifecycle_and_chronology(): void
    {
        $statuses = Appointment::select('status')->distinct()->get()
            ->pluck('status')
            ->map(fn ($s) => $s instanceof AppointmentStatus ? $s->value : (string) $s)
            ->toArray();

        $this->assertContains(AppointmentStatus::COMPLETED->value, $statuses);
        $this->assertContains(AppointmentStatus::CANCELLED->value, $statuses);
        $this->assertContains(AppointmentStatus::NO_SHOW->value, $statuses);
        $this->assertContains(AppointmentStatus::CONFIRMED->value, $statuses);

        // Cancelled appointments must have a reason
        $cancelledWithoutReason = Appointment::where('status', AppointmentStatus::CANCELLED->value)
            ->where(function ($q) {
                $q->whereNull('cancellation_reason')->orWhere('cancellation_reason', '');
            })
            ->count();
        $this->assertSame(0, $cancelledWithoutReason, 'Cancelled appointments must have reasons');

        // Chronology check: start_date <= end_date
        $invalidDates = Appointment::whereRaw('start_date > end_date')->count();
        $this->assertSame(0, $invalidDates, 'Appointments must have start_date <= end_date');

        // Edge case: Terminated employee (ID 28 terminated 2026-03-31) must have 0 appointments after termination
        $postTermAppts = Appointment::where('provider_id', 28)
            ->where('start_date', '>', '2026-03-31 23:59:59')
            ->count();
        $this->assertSame(0, $postTermAppts, 'Terminated employee must have no appointments post-termination');
    }

    /**
     * Test simulation determinism and reproducibility.
     */
    public function test_simulation_configuration_is_deterministic(): void
    {
        $config1 = new SimulationConfig(null, null, 12345);
        $val1 = $config1->randomInt(1, 1000);

        $config2 = new SimulationConfig(null, null, 12345);
        $val2 = $config2->randomInt(1, 1000);

        $this->assertSame($val1, $val2, 'Identical seeds must produce identical pseudorandom outputs');
    }
}
