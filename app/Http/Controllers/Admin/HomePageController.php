<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\SalesInvoiceDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Auth;

class HomePageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userBranchId = Auth::user()->employee->branch->id;

        // If the user's branch ID is 1, sum across all branches; otherwise, sum for the user's branch only
        $expenseAmountQuery = Expense::where('status', 'active')->whereDate('created_at', today());
        $creditCardSalesQuery = SalesInvoice::where('status', 'active')->where('payment_method_id', 2)->whereDate('created_at', today());
        $cashSalesQuery = SalesInvoice::where('status', 'active')->where('payment_method_id', 1)->whereDate('created_at', today());

        // Apply branch filtering if the user's branch ID is not 1
        if ($userBranchId != 1) {
            $expenseAmountQuery->where('branch_id', $userBranchId);
            $creditCardSalesQuery->where('branch_id', $userBranchId);
            $cashSalesQuery->where('branch_id', $userBranchId);
        }

        // Get the sums
        $expenseAmount = $expenseAmountQuery->sum('amount');
        $creditCardSales = $creditCardSalesQuery->sum('net_total');
        $cashSales = $cashSalesQuery->sum('net_total');



        $net_profit =  ($creditCardSales +  $cashSales) - $expenseAmount ;

        $total_customers_today = Customer::whereDate('created_at', today())->count();
        $total_customers = Customer::count();


        $get_biggest_provider_that_have_orders_today = SalesInvoiceDetail::selectRaw('provider_id, COUNT(*) as order_count')
        ->whereDate('created_at', today())
        ->groupBy('provider_id')
        ->orderBy('order_count', 'desc')
        ->first();
        $get_biggest_provider_that_have_orders_today_name = $get_biggest_provider_that_have_orders_today?->provider?->name ?? '' ;


        $get_biggest_service_that_have_orders_today = SalesInvoiceDetail::selectRaw('service_id, COUNT(*) as order_count')
        ->whereDate('created_at', today())
        ->whereNotNull('service_id')
        ->groupBy('service_id')
        ->orderBy('order_count', 'desc')
        ->first();
        $get_biggest_service_that_have_orders_today_name = $get_biggest_service_that_have_orders_today?->service?->name ?? '' ;



        return view('admin.home', compact(

            'total_customers_today',
            'total_customers',
            'net_profit',
            'get_biggest_provider_that_have_orders_today_name',
            'get_biggest_service_that_have_orders_today_name',

            'expenseAmount',
            'creditCardSales',
            'cashSales'
        ));
    }


}
