<?php

use App\Http\Controllers\Internal\StoreMessageController;
use App\Http\Controllers\Internal\ValidateCredentialsController;
use App\Http\Controllers\Internal\ValidateRecipientController;
use App\Http\Middleware\AuthenticateSmtpService;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateSmtpService::class)
    ->prefix('api/internal/smtp')
    ->name('internal.smtp.')
    ->group(function () {
        Route::post('recipients/validate', ValidateRecipientController::class)->name('recipients.validate');
        Route::post('credentials/validate', ValidateCredentialsController::class)->name('credentials.validate');
        Route::post('messages', StoreMessageController::class)->name('messages.store');
    });
