<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ToolController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\EmployeeLevelController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\AdminPanelSettingController;
use App\Http\Controllers\Admin\InventoryTransactionController;

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

Route::get('/', function () {
    return redirect()->route('home.index');
});



Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])->group(function () {

    Route::get('/', [AdminController::class, 'index'])->name('home.index');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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
    Route::resource('customers',CustomerController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('purchase_invoices', PurchaseInvoiceController::class);
    Route::resource('inventories', InventoryController::class);


    /* Transfer In */
    Route::get('inventory_transactions/transfer_in', [InventoryTransactionController::class, 'transferInView'])
    ->name('inventory_transactions.transferInView');
    Route::post('inventory_transactions/transfer_in', [InventoryTransactionController::class,'transferIn'])
    ->name('inventory_transactions.transferIn');

    /* Transfer Out */
    Route::get('inventory_transactions/transfer_out', [InventoryTransactionController::class, 'transferOutView'])
    ->name('inventory_transactions.transferOutView');
    Route::post('inventory_transactions/transfer_out', [InventoryTransactionController::class, 'transferOut'])
    ->name('inventory_transactions.transferOut');


    /* sales invoice  */
    Route::resource('sales_invoices', SalesInvoiceController::class);

    Route::get('/get-items', [SalesInvoiceController::class, 'getItem'])->name('sales_invoices.getItem');
    Route::get('/get-related-employees', [EmployeeController::class, 'getRelatedEmployees'])->name('sales_invoices.getRelatedEmployees');



});
require __DIR__ . '/auth.php';
