<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InboxPasswordController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageRawController;
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

    Route::resource('inboxes', InboxController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::put('inboxes/{inbox}/password', [InboxPasswordController::class, 'update'])->name('inboxes.password.update');

    Route::prefix('inboxes/{inbox}')->scopeBindings()->group(function () {
        Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('inboxes.messages.destroy');
        Route::get('messages/{message}/raw', [MessageRawController::class, 'show'])->name('inboxes.messages.raw');
        Route::get('messages/{message}/attachments/{attachment}', [AttachmentController::class, 'show'])
            ->name('inboxes.messages.attachments.show');
    });
});

require __DIR__.'/settings.php';
