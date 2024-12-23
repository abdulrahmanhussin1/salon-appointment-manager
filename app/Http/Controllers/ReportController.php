<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\SalesInvoiceDetail;

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

            $totalServicesRevenue = SalesInvoiceDetail::whereHas('salesInvoice')
                ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->whereHas('service')
                ->sum('subtotal');

            $totalProductsRevenue = SalesInvoiceDetail::whereHas('salesInvoice')
                ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->whereHas('product')
                ->sum('subtotal');

            $totalTaxes = SalesInvoiceDetail::whereHas('salesInvoice')
                ->whereHas('salesInvoice', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->sum('tax');

            $totalCustomerDeposits = SalesInvoice::whereBetween('invoice_date', [$fromDate, $toDate])
                ->sum('invoice_deposit');


            return response()->json([
                'total_services_revenue' => $totalServicesRevenue,
                'total_products_revenue' => $totalProductsRevenue,
                'total_sales' => $totalServicesRevenue + $totalProductsRevenue,
                'total_taxes' => $totalTaxes,
                'total_customer_deposits' => $totalCustomerDeposits,
                'total_other_expenses' => 222000,
            ]);
        }

        return view('admin.pages.reports.daily_revenues');
    }


}
