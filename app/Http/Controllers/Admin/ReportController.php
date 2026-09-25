<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Traits\HasBranchFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{
    use HasBranchFilter;

    public function dailyRevenues(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            $fromDateStr = Carbon::parse($request->from_date)->toDateString();
            $toDateStr = Carbon::parse($request->to_date)->toDateString();
            $fromDateTime = Carbon::parse($request->from_date)->startOfDay();
            $toDateTime = Carbon::parse($request->to_date)->endOfDay();
            $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

            // Calculate total services revenue, excluding tax and deposits
            $totalServicesRevenue = SalesInvoiceDetail::whereHas('salesInvoice', function ($query) use ($fromDateStr, $toDateStr, $effectiveBranchId) {
                $query->whereBetween('invoice_date', [$fromDateStr, $toDateStr])
                    ->where('status', 'active')
                    ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId));
            })
                ->whereNotNull('service_id') // Ensure it's a service
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));

            // Calculate total products revenue, excluding tax and deposits
            $totalProductsRevenue = SalesInvoiceDetail::whereHas('salesInvoice', function ($query) use ($fromDateStr, $toDateStr, $effectiveBranchId) {
                $query->whereBetween('invoice_date', [$fromDateStr, $toDateStr])
                    ->where('status', 'active')
                    ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId));
            })
                ->whereNotNull('product_id') // Ensure it's a product
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));

            // Aggregate sales invoice data within the date range, excluding deposits
            $data = SalesInvoice::whereBetween('invoice_date', [$fromDateStr, $toDateStr])
                ->where('status', 'active')
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->selectRaw('
                SUM(invoice_tax) AS total_taxes,
                SUM(net_total) AS total_sales,
                SUM(paid_amount_cash) AS total_cash_revenue,
                SUM(payment_method_value) AS total_other_payment_methods
            ')->first();

            // Calculate total customer deposits within the date range
            $totalDeposits = SalesInvoice::whereBetween('invoice_date', [$fromDateStr, $toDateStr])
                ->where('status', 'active')
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->sum('invoice_deposit');

            // Calculate total expenses across all payment methods (REQ-016)
            $expensesQuery = Expense::whereBetween('paid_at', [$fromDateTime, $toDateTime])
                ->where('status', 'active')
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId));

            $totalExpenses = (clone $expensesQuery)->sum('paid_amount');

            $cashPaymentMethod = PaymentMethod::where('name', 'cash')->first();
            $cashExpenses = $cashPaymentMethod
                ? (clone $expensesQuery)->where('payment_method_id', $cashPaymentMethod->id)->sum('paid_amount')
                : 0;
            $nonCashExpenses = $totalExpenses - $cashExpenses;

            // Return the response in the required format
            return response()->json([
                'total_services_revenue' => $totalServicesRevenue ?? 0,
                'total_products_revenue' => $totalProductsRevenue ?? 0,
                'total_sales' => $totalServicesRevenue + $totalProductsRevenue,
                'total_taxes' => $data->total_taxes ?? 0,
                'total_sales_after_tax' => ($totalServicesRevenue + $totalProductsRevenue + ($data->total_taxes ?? 0)) - $totalDeposits,
                'total_cash_revenue' => $data->total_cash_revenue ?? 0,
                'total_other_payment_methods_revenue' => $data->total_other_payment_methods ?? 0,
                'total_other_expenses' => $totalExpenses ?? 0,
                'total_cash_expenses' => $cashExpenses ?? 0,
                'total_non_cash_expenses' => $nonCashExpenses ?? 0,
                'total_deposits' => $totalDeposits ?? 0, // Include deposits separately
            ]);
        }

        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.pages.reports.daily_revenues', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function TotalDailyRevenuesPage(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.pages.reports.total_daily_revenues', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function TotalDailyRevenues(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        // Get all dates in range
        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        // Bulk load sales, expenses, and customer transactions for the date range
        $allSales = SalesInvoice::whereBetween('invoice_date', [$startDateStr, $endDateStr])
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->get()
            ->groupBy(function ($invoice) {
                return Carbon::parse($invoice->invoice_date)->toDateString();
            });

        $allExpenses = Expense::whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->get()
            ->groupBy(function ($expense) {
                return Carbon::parse($expense->paid_at)->toDateString();
            });

        $allTransactions = CustomerTransaction::whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($effectiveBranchId, function ($q) use ($effectiveBranchId) {
                $q->whereHas('createdBy.employee', fn ($eq) => $eq->where('branch_id', $effectiveBranchId));
            })
            ->get()
            ->groupBy(function ($txn) {
                return Carbon::parse($txn->created_at)->toDateString();
            });

        $data = $dates->map(function ($date) use ($allSales, $allExpenses, $allTransactions) {
            $sales = $allSales->get($date, collect());
            $expenses = $allExpenses->get($date, collect());
            $transactions = $allTransactions->get($date, collect());

            return [
                'date' => $date,
                'total' => $sales->sum('total_amount'),
                'cash' => $sales->sum('paid_amount_cash'),
                'other_payment_methods' => $sales->sum('payment_method_value'),
                'total_expenses' => $expenses->sum('paid_amount'),
                'net_total' => $sales->sum('total_amount') - $expenses->sum('paid_amount'),
                'deposits' => $transactions->where('reference_type', 'deposit')->sum('amount'),
            ];
        });

        return DataTables::of($data)->make(true);
    }

    public function dailySummaryPage(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.pages.reports.daily_summary', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function dailySummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        // Get all dates in range
        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        // Bulk load sales with details for the date range
        $allSales = SalesInvoice::with('salesInvoiceDetails')
            ->whereBetween('invoice_date', [$startDateStr, $endDateStr])
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->get()
            ->groupBy(function ($invoice) {
                return Carbon::parse($invoice->invoice_date)->toDateString();
            });

        // Bulk load purchases for the date range
        $allPurchases = PurchaseInvoice::whereBetween('invoice_date', [$startDateStr, $endDateStr])
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->get()
            ->groupBy(function ($invoice) {
                return Carbon::parse($invoice->invoice_date)->toDateString();
            });

        // Bulk load expenses for the date range
        $allExpenses = Expense::whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->get()
            ->groupBy(function ($expense) {
                return Carbon::parse($expense->paid_at)->toDateString();
            });

        $data = $dates->map(function ($date) use ($allSales, $allPurchases, $allExpenses) {
            $dailySales = $allSales->get($date, collect());
            $dailyPurchases = $allPurchases->get($date, collect());
            $expenses = $allExpenses->get($date, collect());

            $dailyDetails = $dailySales->flatMap->salesInvoiceDetails;

            // Separate service and product sales
            $serviceSales = $dailyDetails->whereNotNull('service_id');
            $productSales = $dailyDetails->whereNotNull('product_id');

            // Calculate employee statistics (only for services)
            $employeeStats = $serviceSales->groupBy('provider_id')
                ->map(function ($items) {
                    return [
                        'services_count' => $items->count(),
                        'total_amount' => $items->sum('subtotal'),
                    ];
                });

            // Calculate net sales (total - discount)
            $totalSalesDiscount = $dailySales->sum('invoice_discount');
            $grossSales = $dailyDetails->sum('subtotal');
            $netSales = $grossSales - $totalSalesDiscount;

            // Calculate net purchases (total - discount)
            $totalPurchasesAmount = $dailyPurchases->sum('total_amount');
            $totalPurchasesDiscount = $dailyPurchases->sum('invoice_discount');
            $netPurchases = $totalPurchasesAmount - $totalPurchasesDiscount;

            return [
                'date' => $date,
                'total_expenses' => $expenses->sum('paid_amount'),
                'total_customers' => $dailySales->count(),
                'total_employees' => $employeeStats->count(),
                // Service related metrics
                'services_count' => $serviceSales->count(),
                'services_sales' => $serviceSales->sum('subtotal'),
                'services_commissions' => $serviceSales->sum('commission_amount'),
                // Product related metrics
                'products_count' => $productSales->count(),
                'products_sales' => $productSales->sum('subtotal'),
                // Purchase metrics
                'purchases_count' => $dailyPurchases->count(),
                'purchases_amount' => $totalPurchasesAmount,
                'purchases_discount' => $totalPurchasesDiscount,
                'net_purchases' => $netPurchases,
                // Sales metrics
                'gross_sales' => $grossSales,
                'sales_discount' => $totalSalesDiscount,
                'net_sales' => $netSales,
                // Overall metrics
                'avg_customer_value' => $dailySales->count() > 0
                    ? $netSales / $dailySales->count()
                    : 0,
                'avg_employee_productivity' => $employeeStats->count() > 0
                    ? $serviceSales->count() / $employeeStats->count()
                    : 0,
            ];
        });

        return DataTables::of($data)->make(true);
    }

    public function monthlySummaryPage(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.pages.reports.monthly_summary', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function monthlySummary(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthField = fn (string $col) => $isSqlite
            ? DB::raw("CAST(strftime('%m', {$col}) AS INTEGER) as month")
            : DB::raw("MONTH({$col}) as month");

        // Get Services Revenue
        $servicesRevenueQuery = DB::table('sales_invoice_details')
            ->join('sales_invoices', 'sales_invoice_details.sales_invoice_id', '=', 'sales_invoices.id')
            ->whereNotNull('service_id')
            ->whereYear('sales_invoices.invoice_date', $year)
            ->where('sales_invoices.status', 'active');

        if ($effectiveBranchId) {
            $servicesRevenueQuery->where('sales_invoices.branch_id', $effectiveBranchId);
        }

        $servicesRevenue = $servicesRevenueQuery->select(
            $monthField('sales_invoices.invoice_date'),
            DB::raw('SUM(sales_invoice_details.subtotal) as total')
        )
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get Products Revenue
        $productsRevenueQuery = DB::table('sales_invoice_details')
            ->join('sales_invoices', 'sales_invoice_details.sales_invoice_id', '=', 'sales_invoices.id')
            ->whereNotNull('product_id')
            ->whereYear('sales_invoices.invoice_date', $year)
            ->where('sales_invoices.status', 'active');

        if ($effectiveBranchId) {
            $productsRevenueQuery->where('sales_invoices.branch_id', $effectiveBranchId);
        }

        $productsRevenue = $productsRevenueQuery->select(
            $monthField('sales_invoices.invoice_date'),
            DB::raw('SUM(sales_invoice_details.subtotal) as total')
        )
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get Expenses
        $expenses = Expense::select(
            $monthField('paid_at'),
            DB::raw('SUM(paid_amount) as total')
        )
            ->whereYear('paid_at', $year)
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get Purchases
        $purchases = PurchaseInvoice::select(
            $monthField('invoice_date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->whereYear('invoice_date', $year)
            ->where('status', 'active')
            ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get Provider Counts
        $providerCountsQuery = DB::table('sales_invoice_details')
            ->join('sales_invoices', 'sales_invoice_details.sales_invoice_id', '=', 'sales_invoices.id')
            ->whereYear('sales_invoices.invoice_date', $year)
            ->where('sales_invoices.status', 'active');

        if ($effectiveBranchId) {
            $providerCountsQuery->where('sales_invoices.branch_id', $effectiveBranchId);
        }

        $providerCounts = $providerCountsQuery->select(
            $monthField('sales_invoices.invoice_date'),
            DB::raw('COUNT(DISTINCT provider_id) as total')
        )
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get New Customers
        $newCustomersQuery = Customer::select(
            $monthField('created_at'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', $year);

        if ($effectiveBranchId) {
            $newCustomersQuery->where(function ($q) use ($effectiveBranchId) {
                $q->whereHas('createdBy.employee', fn ($eq) => $eq->where('branch_id', $effectiveBranchId))
                    ->orWhereHas('salesInvoices', fn ($sq) => $sq->where('branch_id', $effectiveBranchId));
            });
        }

        $newCustomers = $newCustomersQuery->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Prepare data for DataTables
        $data = [];
        foreach (range(1, 12) as $month) {
            $monthServices = $servicesRevenue[$month] ?? 0;
            $monthProducts = $productsRevenue[$month] ?? 0;
            $monthExpenses = $expenses[$month] ?? 0;
            $netIncome = ($monthServices + $monthProducts) - $monthExpenses;

            $data[] = [
                'metric' => date('F', mktime(0, 0, 0, $month, 1)),
                'services' => number_format($monthServices, 2),
                'products' => number_format($monthProducts, 2),
                'expenses' => number_format($monthExpenses, 2),
                'net_income' => number_format($netIncome, 2),
                'purchases' => number_format($purchases[$month] ?? 0, 2),
                'provider_count' => $providerCounts[$month] ?? 0,
                'new_customers' => $newCustomers[$month] ?? 0,
            ];
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->rawColumns(['metric'])
            ->make(true);
    }

    /**
     * Display the Outstanding Customer Deposits Report page.
     */
    public function customerDeposits(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.pages.reports.customer_deposits', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    /**
     * Return DataTables JSON for Outstanding Customer Deposits.
     */
    public function customerDepositsData(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));
        $balanceFilter = $request->input('balance_filter', 'positive');

        $query = DB::table('customers as c')
            ->join('customer_transactions as ct', 'ct.customer_id', '=', 'c.id')
            ->leftJoin('users as u', 'u.id', '=', 'ct.created_by')
            ->leftJoin('employees as e', 'e.id', '=', 'u.employee_id')
            ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'c.created_by')
            ->leftJoin('employees as ce', 'ce.id', '=', 'cu.employee_id')
            ->leftJoin('branches as cb', 'cb.id', '=', 'ce.branch_id')
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.phone as customer_phone',
                DB::raw("COALESCE(MAX(b.name), MAX(cb.name), 'N/A') as branch_name"),
                DB::raw("COALESCE(SUM(CASE WHEN ct.status = 'available' AND ct.amount > 0 THEN ct.amount ELSE 0 END), 0) as current_balance"),
                DB::raw('COALESCE(SUM(CASE WHEN ct.amount < 0 THEN ABS(ct.amount) ELSE 0 END), 0) as total_used'),
                DB::raw("COALESCE(SUM(CASE WHEN ct.status = 'available' AND ct.amount > 0 THEN ct.amount ELSE 0 END), 0) + COALESCE(SUM(CASE WHEN ct.amount < 0 THEN ABS(ct.amount) ELSE 0 END), 0) as total_deposited"),
                DB::raw('MAX(ct.created_at) as last_activity'),
            ])
            ->when($effectiveBranchId, fn ($q) => $q->where('e.branch_id', $effectiveBranchId))
            ->groupBy('c.id', 'c.name', 'c.phone');

        if ($balanceFilter === 'positive') {
            $query->havingRaw("SUM(CASE WHEN ct.status = 'available' AND ct.amount > 0 THEN ct.amount ELSE 0 END) > 0");
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_deposited', function ($row) {
                return number_format((float) $row->total_deposited, 2);
            })
            ->editColumn('total_used', function ($row) {
                return number_format((float) $row->total_used, 2);
            })
            ->editColumn('current_balance', function ($row) {
                return number_format((float) $row->current_balance, 2);
            })
            ->editColumn('last_activity', function ($row) {
                return $row->last_activity ? Carbon::parse($row->last_activity)->format('Y-m-d H:i') : '-';
            })
            ->addColumn('balance_badge', function ($row) {
                $bal = (float) $row->current_balance;
                if ($bal > 0) {
                    return '<span class="badge bg-success">'.number_format($bal, 2).'</span>';
                }

                return '<span class="badge bg-secondary">0.00</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('customers.edit', $row->customer_id);

                return '<a href="'.$editUrl.'" class="btn btn-sm btn-outline-primary" title="View Customer"><i class="bi bi-eye"></i></a>';
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->where('c.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('customer_phone', function ($query, $keyword) {
                $query->where('c.phone', 'like', "%{$keyword}%");
            })
            ->orderColumn('customer_name', 'c.name $1')
            ->orderColumn('current_balance', 'current_balance $1')
            ->orderColumn('total_used', 'total_used $1')
            ->orderColumn('total_deposited', 'total_deposited $1')
            ->orderColumn('last_activity', 'last_activity $1')
            ->rawColumns(['balance_badge', 'action'])
            ->make(true);
    }

    /**
     * Return JSON KPI statistics for Outstanding Customer Deposits.
     */
    public function customerDepositsStats(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $statsQuery = DB::table('customer_transactions as ct')
            ->leftJoin('users as u', 'u.id', '=', 'ct.created_by')
            ->leftJoin('employees as e', 'e.id', '=', 'u.employee_id')
            ->when($effectiveBranchId, fn ($q) => $q->where('e.branch_id', $effectiveBranchId));

        $totals = $statsQuery->select([
            DB::raw("COALESCE(SUM(CASE WHEN ct.status = 'available' AND ct.amount > 0 THEN ct.amount ELSE 0 END), 0) as total_liability"),
            DB::raw('COALESCE(SUM(CASE WHEN ct.amount < 0 THEN ABS(ct.amount) ELSE 0 END), 0) as total_used'),
        ])->first();

        $totalLiability = (float) ($totals->total_liability ?? 0);
        $totalUsed = (float) ($totals->total_used ?? 0);
        $totalDeposited = $totalLiability + $totalUsed;

        $customersCount = DB::table('customer_transactions as ct')
            ->leftJoin('users as u', 'u.id', '=', 'ct.created_by')
            ->leftJoin('employees as e', 'e.id', '=', 'u.employee_id')
            ->when($effectiveBranchId, fn ($q) => $q->where('e.branch_id', $effectiveBranchId))
            ->where('ct.status', 'available')
            ->where('ct.amount', '>', 0)
            ->distinct('ct.customer_id')
            ->count('ct.customer_id');

        return response()->json([
            'total_liability' => number_format($totalLiability, 2, '.', ''),
            'total_deposited' => number_format($totalDeposited, 2, '.', ''),
            'total_used' => number_format($totalUsed, 2, '.', ''),
            'customers_count' => $customersCount,
        ]);
    }

    /**
     * Display the Appointment Conversion Analytics Report page.
     */
    public function appointmentConversion(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        if ($request->ajax() || $request->wantsJson()) {
            return $this->appointmentConversionStats($request);
        }

        return view('admin.pages.reports.appointment_conversion', compact('branches', 'canSelectAll', 'effectiveBranchId', 'fromDate', 'toDate'));
    }

    /**
     * Return JSON analytics for Appointment Conversion (confirmed -> completed).
     */
    public function appointmentConversionStats(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));
        $fromDate = $request->input('from_date') ? Carbon::parse($request->input('from_date'))->startOfDay() : now()->startOfMonth()->startOfDay();
        $toDate = $request->input('to_date') ? Carbon::parse($request->input('to_date'))->endOfDay() : now()->endOfDay();

        $baseQuery = Appointment::with(['provider', 'service', 'salesInvoice'])
            ->whereBetween('start_date', [$fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s')])
            ->when($effectiveBranchId, function ($query, $branchId) {
                $query->where(function ($sub) use ($branchId) {
                    $sub->whereHas('provider', fn ($q) => $q->where('branch_id', $branchId))
                        ->orWhereHas('service', fn ($q) => $q->where('branch_id', $branchId));
                });
            });

        $appointments = $baseQuery->get();

        $statusOf = fn ($apt) => $apt->status instanceof \BackedEnum ? $apt->status->value : (string) $apt->status;

        $totalBooked = $appointments->filter(fn ($apt) => $statusOf($apt) !== AppointmentStatus::REJECTED->value)->count();
        $requestedCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::REQUESTED->value)->count();
        $confirmedCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::CONFIRMED->value)->count();
        $checkedInCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::CHECKED_IN->value)->count();
        $inServiceCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::IN_SERVICE->value)->count();
        $completedCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::COMPLETED->value)->count();
        $cancelledCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::CANCELLED->value)->count();
        $noShowCount = $appointments->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::NO_SHOW->value)->count();

        // Invoiced appointments: count of appointments that have a linked active sales invoice
        $invoicedAppointments = $appointments->filter(function ($apt) {
            return $apt->salesInvoice && $apt->salesInvoice->status === 'active';
        });
        $invoicedCount = $invoicedAppointments->count();
        $totalRevenue = (float) $invoicedAppointments->sum(fn ($apt) => $apt->salesInvoice->net_total ?? 0);

        // Confirmed pool = confirmed + checked_in + in_service + completed + cancelled + no_show
        $confirmedPool = $confirmedCount + $checkedInCount + $inServiceCount + $completedCount + $cancelledCount + $noShowCount;
        $confirmedToCompletedRate = $confirmedPool > 0 ? round(($completedCount / $confirmedPool) * 100, 1) : 0.0;
        $bookedToCompletedRate = $totalBooked > 0 ? round(($completedCount / $totalBooked) * 100, 1) : 0.0;
        $cancellationRate = $totalBooked > 0 ? round(($cancelledCount / $totalBooked) * 100, 1) : 0.0;
        $noShowRate = $totalBooked > 0 ? round(($noShowCount / $totalBooked) * 100, 1) : 0.0;

        // Provider conversion breakdown
        $providerBreakdown = $appointments->groupBy('provider_id')->map(function ($items, $providerId) use ($statusOf) {
            $provider = $items->first()->provider;
            $total = $items->filter(fn ($apt) => $statusOf($apt) !== AppointmentStatus::REJECTED->value)->count();
            $completed = $items->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::COMPLETED->value)->count();
            $cancelled = $items->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::CANCELLED->value)->count();
            $noShow = $items->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::NO_SHOW->value)->count();
            $confirmedSubPool = $items->filter(fn ($apt) => in_array($statusOf($apt), [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::CHECKED_IN->value,
                AppointmentStatus::IN_SERVICE->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ]))->count();
            $rate = $confirmedSubPool > 0 ? round(($completed / $confirmedSubPool) * 100, 1) : 0.0;

            $rev = $items->filter(fn ($apt) => $apt->salesInvoice && $apt->salesInvoice->status === 'active')
                ->sum(fn ($apt) => $apt->salesInvoice->net_total ?? 0);

            return [
                'provider_id' => $providerId,
                'provider_name' => $provider?->name ?? 'Unknown',
                'total_booked' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'no_show' => $noShow,
                'conversion_rate' => $rate,
                'revenue' => number_format((float) $rev, 2, '.', ''),
            ];
        })->values();

        // Service conversion breakdown
        $serviceBreakdown = $appointments->groupBy('service_id')->map(function ($items, $serviceId) use ($statusOf) {
            $service = $items->first()->service;
            $total = $items->filter(fn ($apt) => $statusOf($apt) !== AppointmentStatus::REJECTED->value)->count();
            $completed = $items->filter(fn ($apt) => $statusOf($apt) === AppointmentStatus::COMPLETED->value)->count();
            $confirmedSubPool = $items->filter(fn ($apt) => in_array($statusOf($apt), [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::CHECKED_IN->value,
                AppointmentStatus::IN_SERVICE->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ]))->count();
            $rate = $confirmedSubPool > 0 ? round(($completed / $confirmedSubPool) * 100, 1) : 0.0;

            return [
                'service_id' => $serviceId,
                'service_name' => $service?->name ?? 'Unknown',
                'total_booked' => $total,
                'completed' => $completed,
                'conversion_rate' => $rate,
            ];
        })->values();

        return response()->json([
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'total_booked' => $totalBooked,
            'requested_count' => $requestedCount,
            'confirmed_count' => $confirmedCount,
            'checked_in_count' => $checkedInCount,
            'in_service_count' => $inServiceCount,
            'completed_count' => $completedCount,
            'cancelled_count' => $cancelledCount,
            'no_show_count' => $noShowCount,
            'invoiced_count' => $invoicedCount,
            'total_revenue' => number_format($totalRevenue, 2, '.', ''),
            'confirmed_to_completed_rate' => $confirmedToCompletedRate,
            'booked_to_completed_rate' => $bookedToCompletedRate,
            'cancellation_rate' => $cancellationRate,
            'no_show_rate' => $noShowRate,
            'provider_breakdown' => $providerBreakdown,
            'service_breakdown' => $serviceBreakdown,
        ]);
    }
}
