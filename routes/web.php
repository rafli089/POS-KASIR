<?php

use App\Http\Controllers\Auth\PinLoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModifierController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [PinLoginController::class, 'show'])->name('login');
    Route::post('/login', [PinLoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [PinLoginController::class, 'logout'])->name('logout');

Route::middleware('auth.pin')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

    Route::middleware('auth.shift')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::post('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
        Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
        Route::get('/shift/close', [ShiftController::class, 'close'])->name('shift.close');
        Route::post('/shift/close', [ShiftController::class, 'closeShift'])->name('shift.close.store');
        Route::get('/shift/activity', [ShiftController::class, 'activity'])->name('shift.activity');
        Route::post('/shift/cash-in', [ShiftController::class, 'cashIn'])->name('shift.cash-in');
        Route::post('/shift/cash-out', [ShiftController::class, 'cashOut'])->name('shift.cash-out');
    });

    Route::get('/shift/open', [ShiftController::class, 'create'])->name('shift.open');
    Route::post('/shift/open', [ShiftController::class, 'store'])->name('shift.open.store');
    Route::get('/shift/current', [ShiftController::class, 'current'])->name('shift.current');
    Route::get('/shift/report', [ShiftController::class, 'report'])->name('shift.report');
    Route::get('/shift/print', [ShiftController::class, 'printReport'])->name('shift.print');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');

    Route::middleware('role.admin')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('/reports/products', [ReportController::class, 'products'])->name('reports.products');
        Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
        Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
        Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');

        Route::get('/modifiers', [ModifierController::class, 'index'])->name('modifiers.index');
        Route::post('/modifiers', [ModifierController::class, 'update'])->name('modifiers.update');

        Route::middleware('role.admin_only')->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        });
    });
});