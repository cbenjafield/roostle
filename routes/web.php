<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:login-code')
        ->name('login.store');

    Route::get('login/code', [LoginController::class, 'code'])->name('login.code');
    Route::post('login/code', [LoginController::class, 'verify'])->name('login.code.verify');
    Route::post('login/code/resend', [LoginController::class, 'resend'])
        ->middleware('throttle:login-code')
        ->name('login.code.resend');

    Route::get('login/name', [LoginController::class, 'name'])->name('login.name');
    Route::post('login/name', [LoginController::class, 'register'])->name('login.name.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
