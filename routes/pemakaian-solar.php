<?php

use App\Http\Controllers\PemakaianSolar\DashboardController;
use App\Http\Controllers\PemakaianSolar\BalanceController;
use App\Http\Controllers\PemakaianSolar\StockController;
use App\Http\Controllers\PemakaianSolar\FillController;
use App\Http\Controllers\PemakaianSolar\ReportController;
use App\Http\Controllers\PemakaianSolar\TransactionController;
use App\Http\Controllers\PemakaianSolar\TripController;
use App\Http\Middleware\PreventDuplicateSubmit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes modul Pemakaian Solar (dimuat oleh RouteServiceProvider)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // --- Dashboard ---
    Route::get('/pemakaian-solar', [DashboardController::class, 'index'])->name('fuel.index');

    Route::prefix('pemakaian-solar')->group(function () {
        // --- Stok Solar: Stok Terkini, Kartu Stok, Penyesuaian & Transfer ---
        Route::get('/stok-solar', [StockController::class, 'index'])
            ->middleware('permission:fuel_stock.view,fuel_stock.adjust')->name('solar.stocks.index');
        Route::post('/stok-solar', [StockController::class, 'adjust'])
            ->middleware(['permission:fuel_stock.adjust', PreventDuplicateSubmit::class])->name('solar.stocks.adjust');
        Route::get('/kartu-stok', [StockController::class, 'ledger'])
            ->middleware('permission:fuel_stock.ledger')->name('solar.stocks.ledger');
        Route::get('/foto-stok/{filename}', [StockController::class, 'photo'])
            ->middleware('permission:fuel_stock.ledger,fuel_stock.view,fuel_stock.adjust')->name('solar.stocks.photo');

        // --- Pemakaian (perjalanan): riwayat & catat ---
        Route::get('/pemakaian', [TripController::class, 'index'])
            ->middleware('permission:fuel_trip.view,fuel_trip.create')->name('solar.trips.index');
        Route::post('/pemakaian', [TripController::class, 'store'])
            ->middleware(['permission:fuel_trip.create', PreventDuplicateSubmit::class])->name('solar.trips.store');

        // --- Saldo solar armada & koreksi ---
        Route::get('/saldo-armada', [BalanceController::class, 'index'])
            ->middleware('permission:fuel_balance.view,fuel_balance.adjust')->name('solar.balances.index');
        Route::post('/saldo-armada', [BalanceController::class, 'adjust'])
            ->middleware(['permission:fuel_balance.adjust', PreventDuplicateSubmit::class])->name('solar.balances.adjust');

        // --- Isi tangki armada dari stok kebun ---
        Route::get('/isi-tangki', [FillController::class, 'index'])
            ->middleware('permission:fuel_fill.view,fuel_fill.create')->name('solar.fills.index');
        Route::post('/isi-tangki', [FillController::class, 'store'])
            ->middleware(['permission:fuel_fill.create', PreventDuplicateSubmit::class])->name('solar.fills.store');

        // --- Laporan Pemakaian Solar per armada ---
        Route::middleware('permission:fuel_report.view')->group(function () {
            Route::get('/laporan', [ReportController::class, 'index'])->name('solar.reports.index');
            Route::get('/laporan/export', [ReportController::class, 'export'])->name('solar.reports.export');
        });

        // --- Pengaturan batas minimum (0 = notifikasi nonaktif) ---
        Route::post('/stok-solar/minimum', [StockController::class, 'setMinimum'])
            ->middleware('permission:fuel_stock.adjust')->name('solar.stocks.minimum');
        Route::post('/saldo-armada/minimum', [BalanceController::class, 'setMinimum'])
            ->middleware('permission:fuel_balance.minimum')->name('solar.balances.minimum');

        // --- Riwayat transaksi hari ini ---
        Route::get('/transaksi-hari-ini', [TransactionController::class, 'index'])->name('solar.transactions.index');
    });
});