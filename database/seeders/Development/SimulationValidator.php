<?php

namespace Database\Seeders\Development;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\InventoryProduct;
use App\Models\Refund;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;

class SimulationValidator
{
    /**
     * Run all validation checks and return a detailed report array.
     */
    public function validate(): array
    {
        $results = [
            'relationships' => $this->validateRelationships(),
            'financial' => $this->validateFinancialIntegrity(),
            'inventory' => $this->validateInventoryIntegrity(),
            'appointments' => $this->validateAppointmentIntegrity(),
            'commissions' => $this->validateCommissionIntegrity(),
            'authorization' => $this->validateBranchScoping(),
        ];

        $allPassed = ! in_array(false, array_column($results, 'passed'), true);
        $results['all_passed'] = $allPassed;

        return $results;
    }

    protected function validateRelationships(): array
    {
        $orphanAppointments = DB::table('appointments')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('customers')->whereColumn('customers.id', 'appointments.customer_id'))
            ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employees')->whereColumn('employees.id', 'appointments.provider_id'))
            ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('services')->whereColumn('services.id', 'appointments.service_id'))
            ->count();

        $orphanInvoices = DB::table('sales_invoices')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('customers')->whereColumn('customers.id', 'sales_invoices.customer_id'))
            ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('branches')->whereColumn('branches.id', 'sales_invoices.branch_id'))
            ->count();

        $orphanDetails = DB::table('sales_invoice_details')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('sales_invoices')->whereColumn('sales_invoices.id', 'sales_invoice_details.sales_invoice_id'))
            ->count();

        $orphanExpenses = DB::table('expenses')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('expense_types')->whereColumn('expense_types.id', 'expenses.expense_type_id'))
            ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('branches')->whereColumn('branches.id', 'expenses.branch_id'))
            ->count();

        $orphanRefunds = DB::table('refunds')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('sales_invoices')->whereColumn('sales_invoices.id', 'refunds.sales_invoice_id'))
            ->count();

        $passed = ($orphanAppointments === 0 && $orphanInvoices === 0 && $orphanDetails === 0 && $orphanExpenses === 0 && $orphanRefunds === 0);

        return [
            'name' => 'Relationship & Foreign Key Integrity',
            'passed' => $passed,
            'details' => [
                'orphan_appointments' => $orphanAppointments,
                'orphan_invoices' => $orphanInvoices,
                'orphan_details' => $orphanDetails,
                'orphan_expenses' => $orphanExpenses,
                'orphan_refunds' => $orphanRefunds,
            ],
        ];
    }

    protected function validateFinancialIntegrity(): array
    {
        $invoices = SalesInvoice::with('salesInvoiceDetails')->get();
        $discrepancyCount = 0;
        $negativeTotalsCount = 0;

        foreach ($invoices as $inv) {
            $computedGross = round((float) $inv->salesInvoiceDetails->sum('subtotal'), 2);
            $grossDiff = abs((float) $inv->total_amount - $computedGross);

            $computedNet = round((float) $inv->total_amount - (float) $inv->invoice_discount + (float) $inv->invoice_tax, 2);
            $netDiff = abs((float) $inv->net_total - $computedNet);

            $computedBalance = round((float) $inv->net_total - (float) $inv->paid_amount_cash - (float) $inv->payment_method_value - (float) $inv->invoice_deposit, 2);
            $balanceDiff = abs((float) $inv->balance_due - $computedBalance);

            if ($grossDiff > 0.10 || $netDiff > 0.10 || $balanceDiff > 0.10) {
                $discrepancyCount++;
            }

            if ($inv->total_amount < 0 || $inv->net_total < 0) {
                $negativeTotalsCount++;
            }
        }

        $passed = ($discrepancyCount === 0 && $negativeTotalsCount === 0);

        return [
            'name' => 'Financial Math & Balancing',
            'passed' => $passed,
            'details' => [
                'total_invoices_checked' => $invoices->count(),
                'discrepancies' => $discrepancyCount,
                'negative_totals' => $negativeTotalsCount,
            ],
        ];
    }

    protected function validateInventoryIntegrity(): array
    {
        $negativeStockCount = InventoryProduct::where('quantity', '<', 0)->count();
        $outOfStockCount = InventoryProduct::where('quantity', '=', 0)->count();
        $totalStockRows = InventoryProduct::count();

        $passed = ($negativeStockCount === 0 && $totalStockRows > 0);

        return [
            'name' => 'Inventory Non-Negativity & Consistency',
            'passed' => $passed,
            'details' => [
                'total_inventory_product_records' => $totalStockRows,
                'negative_stock_records' => $negativeStockCount,
                'out_of_stock_records' => $outOfStockCount,
            ],
        ];
    }

    protected function validateAppointmentIntegrity(): array
    {
        $invalidChronology = Appointment::whereRaw('start_date > end_date')->count();

        $cancelledWithoutReason = Appointment::where('status', AppointmentStatus::CANCELLED->value)
            ->where(function ($q) {
                $q->whereNull('cancellation_reason')->orWhere('cancellation_reason', '');
            })
            ->count();

        // Check terminated employee (ID 28 left 2026-03-31) has zero appointments after termination
        $emp28ApptsAfterTermination = Appointment::where('provider_id', 28)
            ->where('start_date', '>', '2026-03-31 23:59:59')
            ->count();

        $passed = ($invalidChronology === 0 && $cancelledWithoutReason === 0 && $emp28ApptsAfterTermination === 0);

        return [
            'name' => 'Appointment Lifecycle & Chronology',
            'passed' => $passed,
            'details' => [
                'invalid_chronology' => $invalidChronology,
                'cancelled_without_reason' => $cancelledWithoutReason,
                'post_termination_appointments' => $emp28ApptsAfterTermination,
            ],
        ];
    }

    protected function validateCommissionIntegrity(): array
    {
        $invalidCommissions = SalesInvoiceDetail::whereNotNull('service_id')
            ->where('commission_amount', '<', 0)
            ->count();

        $positiveCommissionsCount = SalesInvoiceDetail::whereNotNull('service_id')
            ->where('commission_amount', '>', 0)
            ->count();

        $passed = ($invalidCommissions === 0 && $positiveCommissionsCount > 0);

        return [
            'name' => 'Staff Commission Calculations',
            'passed' => $passed,
            'details' => [
                'negative_commissions' => $invalidCommissions,
                'positive_commissions' => $positiveCommissionsCount,
            ],
        ];
    }

    protected function validateBranchScoping(): array
    {
        $invalidBranchInvoices = SalesInvoice::whereNotIn('branch_id', [1, 2, 3])->count();
        $invalidBranchEmployees = Employee::whereNotIn('branch_id', [1, 2, 3])->count();
        $invalidBranchExpenses = Expense::whereNotIn('branch_id', [1, 2, 3])->count();

        $passed = ($invalidBranchInvoices === 0 && $invalidBranchEmployees === 0 && $invalidBranchExpenses === 0);

        return [
            'name' => 'Branch Scoping & Partitioning',
            'passed' => $passed,
            'details' => [
                'invalid_branch_invoices' => $invalidBranchInvoices,
                'invalid_branch_employees' => $invalidBranchEmployees,
                'invalid_branch_expenses' => $invalidBranchExpenses,
            ],
        ];
    }
}
