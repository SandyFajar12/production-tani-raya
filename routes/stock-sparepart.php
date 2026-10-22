<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\GardenController;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionReportController;
use App\Http\Controllers\UsageController;
use App\Http\Middleware\PreventDuplicateSubmit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes modul Stok Sparepart (dimuat oleh RouteServiceProvider)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // --- Dashboard & ringkasan ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/transaksi-hari-ini', [TransactionController::class, 'index'])->name('transactions.index');

    // --- Laporan ---
    Route::middleware('permission:report.view')->group(function () {
        Route::get('/laporan', [TransactionReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [TransactionReportController::class, 'export'])->name('reports.export');
    });

    // --- Pre-Order ---
    Route::get('/pre-order', [PreorderController::class, 'index'])
        ->middleware('permission:preorder.view,preorder.create,preorder.review,preorder.approve')->name('preorders.index');
    Route::post('/pre-order', [PreorderController::class, 'store'])
        ->middleware(['permission:preorder.create', PreventDuplicateSubmit::class])->name('preorders.store');
    Route::post('/pre-order/{preorder}/ship', [PreorderController::class, 'ship'])
        ->middleware('permission:preorder.review')->name('preorders.ship');
    Route::post('/pre-order/{preorder}/forward', [PreorderController::class, 'forward'])
        ->middleware('permission:preorder.review')->name('preorders.forward');
    Route::post('/pre-order/{preorder}/approve', [PreorderController::class, 'approve'])
        ->middleware('permission:preorder.approve')->name('preorders.approve');
    Route::post('/pre-order/{preorder}/reject', [PreorderController::class, 'reject'])
        ->middleware('permission:preorder.review,preorder.approve')->name('preorders.reject');

    // --- Pemakaian ---
    Route::get('/pemakaian', [UsageController::class, 'index'])
        ->middleware('permission:usage.view,usage.create')->name('usages.index');
    Route::post('/pemakaian', [UsageController::class, 'store'])
        ->middleware(['permission:usage.create', PreventDuplicateSubmit::class])->name('usages.store');

    // --- Pembelian ---
    Route::get('/pembelian', [PurchaseController::class, 'index'])
        ->middleware('permission:purchase.view,purchase.create')->name('purchases.index');
    Route::post('/pembelian', [PurchaseController::class, 'store'])
        ->middleware(['permission:purchase.create', PreventDuplicateSubmit::class])->name('purchases.store');

    // --- Update Stok: Stok Terkini, Kartu Stok, Penyesuaian & Transfer ---
    Route::get('/update-stok', [StockController::class, 'index'])
        ->middleware('permission:stock.view,stock.adjust')->name('stocks.index');
    Route::post('/update-stok', [StockController::class, 'adjust'])
        ->middleware(['permission:stock.adjust', PreventDuplicateSubmit::class])->name('stocks.adjust');
    Route::get('/kartu-stok', [StockController::class, 'ledger'])
        ->middleware('permission:stock.ledger')->name('stocks.ledger');
    Route::get('/stock-photo/{filename}', [StockController::class, 'photo'])
        ->middleware('permission:stock.ledger,stock.view,stock.adjust')->name('stocks.photo');

    // --- Master Sparepart ---
    Route::get('/master/sparepart', [SparepartController::class, 'index'])
        ->middleware('permission:sparepart.view,sparepart.manage')->name('spareparts.index');
    Route::middleware('permission:sparepart.manage')->group(function () {
        Route::post('/master/sparepart', [SparepartController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('spareparts.store');
        Route::put('/master/sparepart/{sparepart}', [SparepartController::class, 'update'])->name('spareparts.update');
        Route::delete('/master/sparepart/{sparepart}', [SparepartController::class, 'destroy'])->name('spareparts.destroy');
    });
});