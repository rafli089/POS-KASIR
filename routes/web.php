<?php

use App\Http\Controllers\Auth\PinLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransactionController;
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
        Route::get('/shift/close', [ShiftController::class, 'close'])->name('shift.close');
        Route::post('/shift/close', [ShiftController::class, 'closeShift'])->name('shift.close.store');
        Route::get('/shift/activity', [ShiftController::class, 'activity'])->name('shift.activity');
    });

    Route::get('/shift/open', [ShiftController::class, 'create'])->name('shift.open');
    Route::post('/shift/open', [ShiftController::class, 'store'])->name('shift.open.store');
    Route::get('/shift/current', [ShiftController::class, 'current'])->name('shift.current');
    Route::get('/shift/report', [ShiftController::class, 'report'])->name('shift.report');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
});