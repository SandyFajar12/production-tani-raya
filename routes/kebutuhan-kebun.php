<?php

use App\Http\Controllers\KebutuhanKebun\DashboardController;
use App\Http\Controllers\KebutuhanKebun\ItemController;
use App\Http\Controllers\KebutuhanKebun\PreorderController;
use App\Http\Controllers\KebutuhanKebun\PurchaseController;
use App\Http\Controllers\KebutuhanKebun\ReportController;
use App\Http\Controllers\KebutuhanKebun\StockController;
use App\Http\Controllers\KebutuhanKebun\TransactionController;
use App\Http\Controllers\KebutuhanKebun\UsageController;
use App\Http\Middleware\PreventDuplicateSubmit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes modul Kebutuhan Kebun (dimuat oleh RouteServiceProvider)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // --- Dashboard ---
    Route::get('/kebutuhan-kebun', [DashboardController::class, 'index'])->name('needs.index');

    Route::prefix('kebutuhan-kebun')->group(function () {

        // --- Master Kebutuhan Kebun ---
        Route::get('/master-item', [ItemController::class, 'index'])
            ->middleware('permission:need_item.view,need_item.manage')->name('kebun.items.index');
        Route::middleware('permission:need_item.manage')->group(function () {
            Route::post('/master-item', [ItemController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('kebun.items.store');
            Route::put('/master-item/{item}', [ItemController::class, 'update'])->name('kebun.items.update');
            Route::delete('/master-item/{item}', [ItemController::class, 'destroy'])->name('kebun.items.destroy');
        });

        // --- Update Stok: Stok Terkini, Kartu Stok, Penyesuaian & Transfer ---
        Route::get('/update-stok', [StockController::class, 'index'])
            ->middleware('permission:need_stock.view,need_stock.adjust')->name('kebun.stocks.index');
        Route::post('/update-stok', [StockController::class, 'adjust'])
            ->middleware(['permission:need_stock.adjust', PreventDuplicateSubmit::class])->name('kebun.stocks.adjust');
        Route::get('/kartu-stok', [StockController::class, 'ledger'])
            ->middleware('permission:need_stock.ledger')->name('kebun.stocks.ledger');
        Route::get('/foto-stok/{filename}', [StockController::class, 'photo'])
            ->middleware('permission:need_stock.ledger,need_stock.view,need_stock.adjust')->name('kebun.stocks.photo');

        // --- Pre-Order ---
        Route::get('/pre-order', [PreorderController::class, 'index'])
            ->middleware('permission:need_preorder.view,need_preorder.create,need_preorder.review,need_preorder.approve')->name('kebun.preorders.index');
        Route::post('/pre-order', [PreorderController::class, 'store'])
            ->middleware(['permission:need_preorder.create', PreventDuplicateSubmit::class])->name('kebun.preorders.store');
        Route::post('/pre-order/{preorder}/ship', [PreorderController::class, 'ship'])
            ->middleware('permission:need_preorder.review')->name('kebun.preorders.ship');
        Route::post('/pre-order/{preorder}/forward', [PreorderController::class, 'forward'])
            ->middleware('permission:need_preorder.review')->name('kebun.preorders.forward');
        Route::post('/pre-order/{preorder}/approve', [PreorderController::class, 'approve'])
            ->middleware('permission:need_preorder.approve')->name('kebun.preorders.approve');
        Route::post('/pre-order/{preorder}/reject', [PreorderController::class, 'reject'])
            ->middleware('permission:need_preorder.review,need_preorder.approve')->name('kebun.preorders.reject');

        // --- Pembelian ---
        Route::get('/pembelian', [PurchaseController::class, 'index'])
            ->middleware('permission:need_purchase.view,need_purchase.create')->name('kebun.purchases.index');
        Route::post('/pembelian', [PurchaseController::class, 'store'])
            ->middleware(['permission:need_purchase.create', PreventDuplicateSubmit::class])->name('kebun.purchases.store');

        // --- Pemakaian ---
        Route::get('/pemakaian', [UsageController::class, 'index'])
            ->middleware('permission:need_usage.view,need_usage.create')->name('kebun.usages.index');
        Route::post('/pemakaian', [UsageController::class, 'store'])
            ->middleware(['permission:need_usage.create', PreventDuplicateSubmit::class])->name('kebun.usages.store');

        // --- Laporan Pemakaian per kebun ---
        Route::middleware('permission:need_report.view')->group(function () {
            Route::get('/laporan', [ReportController::class, 'index'])->name('kebun.reports.index');
            Route::get('/laporan/export', [ReportController::class, 'export'])->name('kebun.reports.export');
        });

        // --- Riwayat transaksi hari ini ---
        Route::get('/transaksi-hari-ini', [TransactionController::class, 'index'])->name('kebun.transactions.index');
    });
});