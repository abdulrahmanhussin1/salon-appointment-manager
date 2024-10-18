<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Settings\AdminPanelSettingController;

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
    return view('welcome');
});



Route::prefix('admin')->middleware(['auth', 'verified','checkRole'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('home.index');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('/roles', RoleController::class);
    Route::resource('/users', UserController::class);

    Route::get('/admin_panel_settings', [AdminPanelSettingController::class, 'index'])->name('admin_panel_settings.index');
    Route::put('/admin_panel_settings/{id}', [AdminPanelSettingController::class, 'update'])->name('admin_panel_settings.update');
});
require __DIR__ . '/auth.php';
