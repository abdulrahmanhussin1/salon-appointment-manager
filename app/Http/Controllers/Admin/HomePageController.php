<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Expense;
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

        return view('admin.home', compact('expenseAmount', 'creditCardSales', 'cashSales'));
    }


}
