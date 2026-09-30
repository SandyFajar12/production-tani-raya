<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — TaniRaya ERP
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ---------------------- Auth (tanpa login) ----------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/lupa-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------- Halaman yang butuh login ----------------------
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/transaksi-hari-ini', [TransactionController::class, 'index'])->name('transactions.index');

    // Menu Transaksi
    Route::get('/pre-order', [PreorderController::class, 'index'])->name('preorders.index');
    Route::post('/pre-order', [PreorderController::class, 'store'])->name('preorders.store');
    Route::post('/pre-order/{preorder}/approve', [PreorderController::class, 'approve'])->name('preorders.approve');
    Route::post('/pre-order/{preorder}/reject', [PreorderController::class, 'reject'])->name('preorders.reject');

    Route::get('/pemakaian', [UsageController::class, 'index'])->name('usages.index');
    Route::post('/pemakaian', [UsageController::class, 'store'])->name('usages.store');

    Route::get('/pembelian', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/pembelian', [PurchaseController::class, 'store'])->name('purchases.store');

    Route::get('/update-stok', [StockController::class, 'index'])->name('stocks.index');
    Route::post('/update-stok', [StockController::class, 'adjust'])->name('stocks.adjust');
    Route::get('/kartu-stok', [StockController::class, 'ledger'])->name('stocks.ledger');

    // Menu Master
    Route::get('/master/sparepart', [SparepartController::class, 'index'])->name('spareparts.index');
    Route::post('/master/sparepart', [SparepartController::class, 'store'])->name('spareparts.store');
    Route::put('/master/sparepart/{sparepart}', [SparepartController::class, 'update'])->name('spareparts.update');
    Route::delete('/master/sparepart/{sparepart}', [SparepartController::class, 'destroy'])->name('spareparts.destroy');

    Route::get('/master/armada', [FleetController::class, 'index'])->name('fleets.index');
    Route::post('/master/armada', [FleetController::class, 'store'])->name('fleets.store');
    Route::put('/master/armada/{fleet}', [FleetController::class, 'update'])->name('fleets.update');
    Route::delete('/master/armada/{fleet}', [FleetController::class, 'destroy'])->name('fleets.destroy');

    Route::get('/master/supplier', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/master/supplier', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/master/supplier/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/master/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/master/user', [UserController::class, 'index'])->name('users.index');
    Route::post('/master/user', [UserController::class, 'store'])->name('users.store');
    Route::put('/master/user/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/master/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/role-permission', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('/role-permission/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
});
