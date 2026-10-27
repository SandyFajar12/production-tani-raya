<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventDuplicateSubmit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes umum — TaniRaya ERP
| Route modul ada di file masing-masing: routes/stock-sparepart.php, dst.
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ---------------------- Auth (tanpa login) ----------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    // Lupa password dinonaktifkan sementara (belum terhubung ke email sungguhan)
    // Route::get('/lupa-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    // Route::post('/lupa-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------- Halaman yang butuh login ----------------------
Route::middleware('auth')->group(function () {

    // --- Halaman awal & modul yang belum jadi ---
    Route::get('/beranda', function () {
        return view('home');
    })->name('home');

    // --- Profil ---
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/avatar/{filename}', [ProfileController::class, 'avatar'])->name('profile.avatar');

    // --- Notifikasi (hanya milik user yang login) ---
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/jumlah', [NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifikasi/dilihat', [NotificationController::class, 'seen'])->name('notifications.seen');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'read'])->name('notifications.read');

    // --- Master User ---
    Route::get('/master/user', [UserController::class, 'index'])
        ->middleware('permission:user.view,user.manage')->name('users.index');
    Route::middleware('permission:user.manage')->group(function () {
        Route::post('/master/user', [UserController::class, 'store'])->middleware(PreventDuplicateSubmit::class)->name('users.store');
        Route::put('/master/user/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/master/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // --- Role & Permission (hanya SuperAdmin) ---
    Route::middleware('permission:role.manage')->group(function () {
        Route::get('/role-permission', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('/role-permission/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
    });
});