<?php

namespace App\Http\Middleware;

use App\Traits\AppHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use AppHelper;

    public function getRoute()
    {
        return Route::current()->getName();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $type = null;
            $page = null;
            if (str_contains($this->getRoute(), '.')) {
                [$type, $page] = explode('.', $this->getRoute());
            }
            if (
                $this->getRoute() === 'home.index'
                || ($this->getRoute() === 'home.calender' && self::perUSer('appointments.index'))
                || ($page === 'multi_destroy' && self::perUSer($type.'.destroy'))
                || ($page === 'store' && self::perUSer($type.'.create'))
                || ($page === 'export' && self::perUSer($type.'.export'))
                || ($page === 'update' && self::perUSer($type.'.edit'))
                || ($page === 'transfer' && self::perUSer($type.'.transferView'))
                || (in_array($page, ['adjust', 'adjustView', 'check_stock']) && (self::perUSer('inventory_transactions.adjustView') || self::perUSer('inventories.create')))
                || (in_array($page, ['history', 'history_data']) && (self::perUSer('inventory_transactions.adjustView') || self::perUSer('inventories.index')))
                || ($page === 'activate' && self::perUSer($type.'.create'))
                || ($page === 'void' && self::perUSer($type.'.void'))
                || ($page === 'invoice' && self::perUSer($type.'.show'))
                || ($type === 'customer_transactions' && in_array($page, ['get_customer_payments', 'store_customer_payment', 'get_payments', 'store_payment']) && (self::perUSer('customers.index') || self::perUSer('customers.show') || self::perUSer('sales_invoices.create') || self::perUSer('customer_transactions.get_customer_payments') || self::perUSer('customer_transactions.store_customer_payment')))
                || ($type === 'refunds' && in_array($page, ['index', 'create', 'store', 'show', 'invoice_details']) && (self::perUSer('refunds.index') || self::perUSer('refunds.create') || self::perUSer('sales_invoices.create') || self::perUSer('sales_invoices.index')))
                || ($type === 'appointments' && in_array($page, ['confirm', 'cancel', 'check_in', 'start_service', 'complete', 'no_show', 'status']) && self::perUSer('appointments.edit'))

                || ($page === 'daily_revenues' && self::perUSer('reports.index'))
                || ($page === 'TotalDailyRevenuesPage' && self::perUSer('reports.index'))
                || (in_array($page, ['TotalDailyRevenues', 'total_daily_revenues']) && self::perUSer('reports.index'))
                || ($page === 'dailySummaryPage' && self::perUSer('reports.index'))
                || (in_array($page, ['dailySummary', 'daily_summary']) && self::perUSer('reports.index'))
                || ($page === 'bookAppointment' && self::perUSer('sales_invoices.create'))
                || ($page === 'monthlySummaryPage' && self::perUSer('reports.index'))
                || (in_array($page, ['monthlySummary', 'monthly_summary']) && self::perUSer('reports.index'))

                || ($page === 'employee-services' && self::perUSer('reports.index'))
                || ($page === 'employee-services.data' && self::perUSer('reports.index'))
                || ($page === 'employee-services.stats' && self::perUSer('reports.index'))

                || ($page === 'employee-summary-services' && self::perUSer('reports.index'))
                || ($page === 'employee-summary-services.data' && self::perUSer('reports.index'))
                || ($page === 'employee-summary-services.stats' && self::perUSer('reports.index'))
                || ($page === 'stock' && self::perUSer('reports.index'))
                || ($page === 'stock_report' && self::perUSer('reports.index'))
                || ($page === 'stock_balance' && self::perUSer('reports.index'))
                || ($page === 'stock_balance_transfer' && self::perUSer('reports.index'))
                || (in_array($page, ['customer_deposits', 'customer_deposits_data', 'customer_deposits_stats', 'appointment_conversion', 'appointment_conversion_stats']) && self::perUSer('reports.index'))

                || ($page === 'getDetails' && self::perUSer('sales_invoices.create'))
                || ($page === 'getByType' && self::perUSer('sales_invoices.create'))
                || ($page === 'getByCategory' && self::perUSer('sales_invoices.create'))

                || self::perUSer($this->getRoute())
                || in_array($this->getRoute(), ['dashboard', 'sales_invoices.getItem', 'sales_invoices.getRelatedEmployees'])
                || str_starts_with($this->getRoute() ?? '', 'dashboard.')
                || ($this->getRoute() === 'api.appointments' && (self::perUSer('appointments.index') || (auth()->check() && auth()->user()->hasRole('provider'))))
            ) {
                return $next($request);
            }

            return abort(403);
        }

        return redirect()->route('redirect');
    }
}
