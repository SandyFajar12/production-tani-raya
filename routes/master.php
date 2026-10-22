<?php

use App\Http\Controllers\FleetController;
use App\Http\Controllers\GardenController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\PreventDuplicateSubmit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes master bersama lintas modul (Kebun, Armada, Supplier)
| Dimuat oleh RouteServiceProvider
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // --- Master Armada ---
    Route::get('/master/armada', [FleetController::class, 'index'])
        ->middleware('permission:fleet.view,fleet.manage,fuel_fleet.view,fuel_fleet.manage')->name('fleets.index');
    Route::middleware('permission:fleet.manage,fuel_fleet.manage')->group(function () {
        Route::post('/master/armada', [FleetController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('fleets.store');
        Route::put('/master/armada/{fleet}', [FleetController::class, 'update'])->name('fleets.update');
        Route::delete('/master/armada/{fleet}', [FleetController::class, 'destroy'])->name('fleets.destroy');
    });

    // --- Master Supplier ---
    Route::get('/master/supplier', [SupplierController::class, 'index'])
        ->middleware('permission:supplier.view,supplier.manage,need_supplier.view,need_supplier.manage,fuel_supplier.view,fuel_supplier.manage')->name('suppliers.index');
    Route::middleware('permission:supplier.manage,need_supplier.manage,fuel_supplier.manage')->group(function () {
        Route::post('/master/supplier', [SupplierController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('suppliers.store');
        Route::put('/master/supplier/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/master/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // --- Master Kebun ---
    Route::get('/master/kebun', [GardenController::class, 'index'])
        ->middleware('permission:garden.view,garden.manage,need_garden.view,need_garden.manage,fuel_garden.view,fuel_garden.manage')->name('gardens.index');
    Route::middleware('permission:garden.manage,need_garden.manage,fuel_garden.manage')->group(function () {
        Route::post('/master/kebun', [GardenController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('gardens.store');
        Route::put('/master/kebun/{garden}', [GardenController::class, 'update'])->name('gardens.update');
        Route::delete('/master/kebun/{garden}', [GardenController::class, 'destroy'])->name('gardens.destroy');
    });
});