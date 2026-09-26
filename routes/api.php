<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CustomerTransactionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeReportController;
use App\Http\Controllers\Admin\EmployeeSummaryReportController;
use App\Http\Controllers\Admin\InventoryTransactionController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\StockReportController;
use App\Http\Controllers\Admin\StoreBalanceReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['web', 'auth', 'checkRole'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard API Endpoints
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary'])->name('summary');
        Route::get('/revenue', [DashboardController::class, 'revenue'])->name('revenue');
        Route::get('/appointments', [DashboardController::class, 'appointments'])->name('appointments');
        Route::get('/staff', [DashboardController::class, 'staff'])->name('staff');
        Route::get('/inventory-alerts', [DashboardController::class, 'inventoryAlerts'])->name('inventory_alerts');
        Route::get('/expenses', [DashboardController::class, 'expenses'])->name('expenses');
        Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
        Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');
    });

    /*
    |--------------------------------------------------------------------------
    | Appointments API (Feed & Status Actions)
    |--------------------------------------------------------------------------
    */
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('api.appointments');
    Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{id}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check_in');
    Route::post('/appointments/{id}/start-service', [AppointmentController::class, 'startService'])->name('appointments.start_service');
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{id}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no_show');
    Route::post('/appointments/{id}/status', [AppointmentController::class, 'changeStatus'])->name('appointments.status');

    /*
    |--------------------------------------------------------------------------
    | Inventory Transactions API
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory_transactions')->name('inventory_transactions.')->group(function () {
        Route::get('/check_stock', [InventoryTransactionController::class, 'checkStock'])->name('check_stock');
        Route::get('/history_data', [InventoryTransactionController::class, 'historyData'])->name('history_data');
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Invoices / POS AJAX API
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [SalesInvoiceController::class, 'getByType'])->name('sales_invoices.getByType');
    Route::get('/items', [SalesInvoiceController::class, 'getByCategory'])->name('sales_invoices.getByCategory');
    Route::get('/items/{id}', [SalesInvoiceController::class, 'getDetails'])->name('sales_invoices.getDetails');
    Route::get('/get-items', [SalesInvoiceController::class, 'getItem'])->name('sales_invoices.getItem');
    Route::get('/get-related-employees', [EmployeeController::class, 'getRelatedEmployees'])->name('sales_invoices.getRelatedEmployees');

    /*
    |--------------------------------------------------------------------------
    | Refunds API
    |--------------------------------------------------------------------------
    */
    Route::get('/refunds/invoice-details/{id}', [RefundController::class, 'getInvoiceDetails'])->name('refunds.invoice_details');

    /*
    |--------------------------------------------------------------------------
    | Customer Transactions API
    |--------------------------------------------------------------------------
    */
    Route::prefix('customer_transactions')->name('customer_transactions.')->group(function () {
        Route::get('/get_payments', [CustomerTransactionController::class, 'getCustomerPayments'])->name('get_customer_payments');
        Route::post('/store_payment', [CustomerTransactionController::class, 'storeCustomerPayment'])->name('store_customer_payment');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports JSON Data & Stats API
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('report.')->group(function () {
        Route::get('/employee-summary-services/data', [EmployeeSummaryReportController::class, 'getData'])->name('employee-summary-services.data');
        Route::get('/employee-summary-services/stats', [EmployeeSummaryReportController::class, 'getStats'])->name('employee-summary-services.stats');

        Route::get('/employee-services/data', [EmployeeReportController::class, 'getData'])->name('employee-services.data');
        Route::get('/employee-services/stats', [EmployeeReportController::class, 'getEmployeeStats'])->name('employee-services.stats');

        Route::get('/stock/data', [StockReportController::class, 'getData'])->name('stock_report');
        Route::get('/store-balance/data', [StoreBalanceReportController::class, 'getData'])->name('stock_balance_transfer');

        Route::get('/customer-deposits/data', [ReportController::class, 'customerDepositsData'])->name('customer_deposits_data');
        Route::get('/customer-deposits/stats', [ReportController::class, 'customerDepositsStats'])->name('customer_deposits_stats');

        Route::get('/appointment-conversion/stats', [ReportController::class, 'appointmentConversionStats'])->name('appointment_conversion_stats');

        Route::match(['get', 'post'], '/total_daily_revenues', [ReportController::class, 'TotalDailyRevenues'])->name('total_daily_revenues');
        Route::match(['get', 'post'], '/daily_summary', [ReportController::class, 'dailySummary'])->name('daily_summary');
        Route::match(['get', 'post'], '/monthly_summary', [ReportController::class, 'monthlySummary'])->name('monthly_summary');
    });

});
