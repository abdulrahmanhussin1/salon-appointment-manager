<?php

use App\Http\Controllers\Admin\AdminPanelSettingController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerTransactionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeLevelController;
use App\Http\Controllers\Admin\EmployeeReportController;
use App\Http\Controllers\Admin\EmployeeSummaryReportController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseTypeController;
use App\Http\Controllers\Admin\HomePageController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryTransactionController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StockReportController;
use App\Http\Controllers\Admin\StoreBalanceReportController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ToolController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\LocalizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('lang/{locale}', [LocalizationController::class, 'switch'])->name('lang.switch');

Route::get('/', function () {
    return redirect()->route('home.index');
});

Route::match(['get', 'post', 'put', 'delete'], 'appointments/{any?}', function () {
    return redirect()->route('appointments.index');
})->where('any', '.*');

Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])->group(function () {

    Route::get('/', [HomePageController::class, 'index'])->name('home.index');
    Route::get('calender', function () {
        return view('admin.calender');
    })->name('home.calender');
    Route::resource('appointments', AppointmentController::class);
    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{id}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check_in');
    Route::post('appointments/{id}/start-service', [AppointmentController::class, 'startService'])->name('appointments.start_service');
    Route::post('appointments/{id}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('appointments/{id}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no_show');
    Route::post('appointments/{id}/status', [AppointmentController::class, 'changeStatus'])->name('appointments.status');

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

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::resource('/roles', RoleController::class);
    Route::resource('/users', UserController::class);

    Route::get('/admin_panel_settings', [AdminPanelSettingController::class, 'index'])->name('admin_panel_settings.index');
    Route::put('/admin_panel_settings/{id}', [AdminPanelSettingController::class, 'update'])->name('admin_panel_settings.update');

    Route::resource('units', UnitController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('product_categories', ProductCategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('employee_levels', EmployeeLevelController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('tools', ToolController::class);
    Route::resource('service_categories', ServiceCategoryController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('purchase_invoices', PurchaseInvoiceController::class);
    Route::resource('inventories', InventoryController::class);
    Route::resource('expense_types', ExpenseTypeController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::resource('payment_methods', PaymentMethodController::class);

    /* Transfer  */
    Route::get('inventory_transactions/transfer', [InventoryTransactionController::class, 'transferView'])
        ->name('inventory_transactions.transferView');
    Route::post('inventory_transactions/transfer', [InventoryTransactionController::class, 'transfer'])
        ->name('inventory_transactions.transfer');

    /* Stock Adjustments (REQ-018) */
    Route::get('inventory_transactions/adjust', [InventoryTransactionController::class, 'adjustView'])
        ->name('inventory_transactions.adjustView');
    Route::post('inventory_transactions/adjust', [InventoryTransactionController::class, 'adjust'])
        ->name('inventory_transactions.adjust');
    Route::get('inventory_transactions/check_stock', [InventoryTransactionController::class, 'checkStock'])
        ->name('inventory_transactions.check_stock');
    Route::get('inventory_transactions/history', [InventoryTransactionController::class, 'history'])
        ->name('inventory_transactions.history');
    Route::get('inventory_transactions/history_data', [InventoryTransactionController::class, 'historyData'])
        ->name('inventory_transactions.history_data');

    /* sales invoice  */
    Route::resource('sales_invoices', SalesInvoiceController::class);
    Route::get('sales_invoices/invoice/{id}', [SalesInvoiceController::class, 'showReceipt'])->name('sales_invoices.invoice');
    Route::post('sales_invoices/{sales_invoice}/activate', [SalesInvoiceController::class, 'activate'])->name('sales_invoices.activate');
    Route::post('sales_invoices/{sales_invoice}/void', [SalesInvoiceController::class, 'void'])->name('sales_invoices.void');
    Route::get('/get-items', [SalesInvoiceController::class, 'getItem'])->name('sales_invoices.getItem');
    Route::get('/get-related-employees', [EmployeeController::class, 'getRelatedEmployees'])->name('sales_invoices.getRelatedEmployees');
    // Route::get('/book_appointment', [SalesInvoiceController::class, 'bookAppointment'])->name('sales_invoices.bookAppointment');

    /* Refunds & Returns */
    Route::get('refunds/invoice-details/{id}', [RefundController::class, 'getInvoiceDetails'])->name('refunds.invoice_details');
    Route::resource('refunds', RefundController::class)->only(['index', 'create', 'store', 'show']);

    /* Customers */

    Route::get('customer_transactions/get_payments', [CustomerTransactionController::class, 'getCustomerPayments'])->name('customer_transactions.get_customer_payments');
    Route::post('customer_transactions/store_payment', [CustomerTransactionController::class, 'storeCustomerPayment'])->name('customer_transactions.store_customer_payment');

    /* Reports */

    Route::get('reports/daily_revenues', [ReportController::class, 'dailyRevenues'])->name('report.daily_revenues');
    Route::get('reports/total_daily_revenues_page', [ReportController::class, 'TotalDailyRevenuesPage'])->name('report.TotalDailyRevenuesPage');
    Route::post('reports/total_daily_revenues', [ReportController::class, 'TotalDailyRevenues'])->name('report.TotalDailyRevenues');
    Route::get('reports/daily_summary_page', [ReportController::class, 'dailySummaryPage'])->name('report.dailySummaryPage');
    Route::post('reports/daily_summary', [ReportController::class, 'dailySummary'])->name('report.dailySummary');
    Route::get('reports/monthly_summary_page', [ReportController::class, 'monthlySummaryPage'])->name('report.monthlySummaryPage');
    Route::get('reports/monthly_summary', [ReportController::class, 'monthlySummary'])->name('report.monthlySummary');

    Route::prefix('reports')->name('report.')->group(function () {
        Route::get('/employee-summary-services', [EmployeeSummaryReportController::class, 'index'])->name('employee-summary-services');
        Route::get('/employee-summary-services/data', [EmployeeSummaryReportController::class, 'getData'])->name('employee-summary-services.data');
        Route::get('/employee-summary-services/stats', [EmployeeSummaryReportController::class, 'getStats'])->name('employee-summary-services.stats');
    });

    Route::get('reports/employee-services', [EmployeeReportController::class, 'index'])->name('report.employee-services');
    Route::get('reports/employee-services/data', [EmployeeReportController::class, 'getData'])->name('report.employee-services.data');
    Route::get('reports/employee-services/stats', [EmployeeReportController::class, 'getEmployeeStats'])->name('report.employee-services.stats');
    Route::get('reports/stock', [StockReportController::class, 'index'])->name('report.stock');
    Route::get('reports/stock/data', [StockReportController::class, 'getData'])->name('report.stock_report');
    Route::get('reports/store-balance', [StoreBalanceReportController::class, 'index'])->name('report.stock_balance');
    Route::get('reports/store-balance/data', [StoreBalanceReportController::class, 'getData'])->name('report.stock_balance_transfer');
    Route::get('reports/customer-deposits', [ReportController::class, 'customerDeposits'])->name('report.customer_deposits');
    Route::get('reports/customer-deposits/data', [ReportController::class, 'customerDepositsData'])->name('report.customer_deposits_data');
    Route::get('reports/customer-deposits/stats', [ReportController::class, 'customerDepositsStats'])->name('report.customer_deposits_stats');
    Route::get('reports/appointment-conversion', [ReportController::class, 'appointmentConversion'])->name('report.appointment_conversion');
    Route::get('reports/appointment-conversion/stats', [ReportController::class, 'appointmentConversionStats'])->name('report.appointment_conversion_stats');

    Route::get('/categories', [salesInvoiceController::class, 'getByType'])->name('sales_invoices.getByType');
    Route::get('/items', [salesInvoiceController::class, 'getByCategory'])->name('sales_invoices.getByCategory');
    Route::get('/items/{id}', [salesInvoiceController::class, 'getDetails'])->name('sales_invoices.getDetails');

});

require __DIR__.'/auth.php';
