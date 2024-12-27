<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Expense;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\PurchaseInvoice;
use Yajra\DataTables\DataTables;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerTransaction;

class ReportController extends Controller
{
    public function dailyRevenues(Request $request)
    {
        if ($request->ajax()) {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            $fromDate = Carbon::parse($request->from_date);
            $toDate = Carbon::parse($request->to_date);

            // Calculate total services revenue, excluding tax and deposits
            $totalServicesRevenue = SalesInvoiceDetail::whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->where('status', 'active');
            })
                ->whereNotNull('service_id') // Ensure it's a service
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));

            // Calculate total products revenue, excluding tax and deposits
            $totalProductsRevenue = SalesInvoiceDetail::whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->where('status', 'active');
            })
                ->whereNotNull('product_id') // Ensure it's a product
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));

            // Aggregate sales invoice data within the date range, excluding deposits
            $data = SalesInvoice::whereBetween('invoice_date', [$fromDate, $toDate])
                ->where('status', 'active')
                ->selectRaw("
                SUM(invoice_tax) AS total_taxes,
                SUM(net_total) AS total_sales,
                SUM(paid_amount_cash) AS total_cash_revenue,
                SUM(payment_method_value) AS total_other_payment_methods
            ")->first();

            // Calculate total customer deposits within the date range
            $totalDeposits = SalesInvoice::whereBetween('invoice_date', [$fromDate, $toDate])
                ->where('status', 'active')
                ->sum('invoice_deposit');

            // Calculate total other expenses
            $paymentMethod = PaymentMethod::where('name', 'cash')->first();
            $expenses = Expense::where('payment_method_id', $paymentMethod->id)
                ->whereBetween('paid_at', [$fromDate, $toDate])
                ->where('status', 'active')
                ->sum('paid_amount');

            // Return the response in the required format
            return response()->json([
                'total_services_revenue' => $totalServicesRevenue ?? 0,
                'total_products_revenue' => $totalProductsRevenue ?? 0,
                'total_sales' => $totalServicesRevenue + $totalProductsRevenue,
                'total_taxes' => $data->total_taxes ?? 0,
                'total_sales_after_tax' => ($totalServicesRevenue + $totalProductsRevenue + ($data->total_taxes ?? 0)) - $totalDeposits,
                'total_cash_revenue' => $data->total_cash_revenue ?? 0,
                'total_other_payment_methods_revenue' => $data->total_other_payment_methods ?? 0,
                'total_other_expenses' => $expenses ?? 0,
                'total_deposits' => $totalDeposits ?? 0, // Include deposits separately
            ]);
        }

        return view('admin.pages.reports.daily_revenues');
    }


    public function TotalDailyRevenuesPage(Request $request)
    {
        return view('admin.pages.reports.total_daily_revenues');

    }
    public function TotalDailyRevenues(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Get all dates in range
        $dates = collect();
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        $data = $dates->map(function ($date) {
            // Get sales data
            $sales = SalesInvoice::whereDate('invoice_date', $date)
                ->where('status', 'active')
                ->get();

            // Get expenses
            $expenses = Expense::whereDate('paid_at', $date)
                ->where('status', 'active')
                ->get();

            // Get customer transactions
            $transactions = CustomerTransaction::whereDate('created_at', $date)->get();

            return [
                'date' => $date,
                'total' => $sales->sum('total_amount'),
                'cash' => $sales->sum('paid_amount_cash'),
                'other_payment_methods' => $sales->sum('payment_method_value'),
                'total_expenses' => $expenses->sum('paid_amount'),
                'net_total' => $sales->sum('total_amount') - $expenses->sum('paid_amount'),
                'deposits' => $transactions->where('reference_type', 'deposit')->sum('amount')
            ];
        });

        return DataTables::of($data)->make(true);
    }


    public function dailySummaryPage(Request $request)
    {
        return view('admin.pages.reports.daily_summary');
    }
    public function dailySummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Get all dates in range
        $dates = collect();
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        $data = $dates->map(function ($date) {
            // Get daily sales data
            $dailySales = SalesInvoice::whereDate('invoice_date', $date)
            ->where('status', 'active')
                ->get();

            // Get daily purchases data
            $dailyPurchases = PurchaseInvoice::whereDate('invoice_date', $date)
            ->where('status', 'active')
                ->get();

            // Get invoice details for the day
            $invoiceIds = $dailySales->pluck('id');
            $dailyDetails = SalesInvoiceDetail::whereIn('sales_invoice_id', $invoiceIds)
                ->with(['provider', 'service', 'product'])
                ->get();

            $expenses = Expense::whereDate('paid_at', $date)
            ->where('status', 'active')
                ->get();


            // Separate service and product sales
            $serviceSales = $dailyDetails->whereNotNull('service_id');
            $productSales = $dailyDetails->whereNotNull('product_id');

            // Calculate employee statistics (only for services)
            $employeeStats = $serviceSales->groupBy('provider_id')
            ->map(function ($items) {
                return [
                    'services_count' => $items->count(),
                    'total_amount' => $items->sum('subtotal')
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
                'services_commissions' => $serviceSales->sum('subtotal'), // Adjust commission calculation as needed
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
                    : 0
            ];
        });

        return DataTables::of($data)->make(true);
    }
}
