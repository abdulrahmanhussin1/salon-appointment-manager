<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;

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

            $fromDate = $request->input('from_date', Carbon::today()->toDateString());
            $toDate = $request->input('to_date', Carbon::today()->toDateString());


            // Calculate total services revenue, excluding tax
            $totalServicesRevenue = SalesInvoiceDetail::whereHas('salesInvoice')
                ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->whereNotNull('service_id')  // Ensure it's a service
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));

            // Calculate total products revenue, excluding tax
            $totalProductsRevenue = SalesInvoiceDetail::whereHas('salesInvoice')
                ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->whereNotNull('product_id')  // Ensure it's a product
                ->sum(DB::raw('(customer_price * quantity) - ((discount / 100) * customer_price * quantity)'));




            // Aggregate the sales invoice data within the date range
            $data = SalesInvoice::whereBetween('invoice_date', [$fromDate, $toDate])
                ->where('status', 'active')  // You can add any necessary filters, such as active invoices
                ->selectRaw("

            SUM(invoice_tax) AS total_taxes,
            SUM(net_total) AS total_sales,
            SUM(paid_amount_cash) AS total_cash_payments,
            SUM(payment_method_value) AS total_other_payment_methods,


            SUM(invoice_deposit) AS total_customer_deposits
        ")
                ->first();

            // Return the response in the required format
            return response()->json([
                'total_services_revenue' => $totalServicesRevenue ?? 0,
                'total_products_revenue' => $totalProductsRevenue ?? 0,  // Assuming total_products_revenue is equal to total_sales
                'total_sales' => $totalServicesRevenue + $totalProductsRevenue,
                'total_taxes' => $data->total_taxes ?? 0,
                'total_sales_after_tax' => $totalServicesRevenue + $totalProductsRevenue + $data->total_taxes,
                'total_cash_payments' => $data->total_cash_payments ?? 0,
                'total_other_expenses' => 0,  // Assuming you don't have this data in the current schema, replace as needed
                'total_other_payment_methods' => $data->total_other_payment_methods ?? 0,
                'total_customer_deposits' => $data->total_customer_deposits ?? 0,
            ]);
        }

        return view('admin.pages.reports.daily_revenues');
    }
}
