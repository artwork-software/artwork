<?php

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalLoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('external.guest')
    ->prefix('external')
    ->name('external.')
    ->group(function (): void {
        Route::get('login', [ExternalLoginController::class, 'showLoginForm'])->name('login.form');
        Route::post('login', [ExternalLoginController::class, 'requestLink'])
            ->middleware('throttle:external-request-link')
            ->name('login.request');
        Route::get('login/{token}', [ExternalLoginController::class, 'redeem'])
            ->middleware('throttle:external-redeem-token')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('login.redeem');

        Route::get('login-sent', fn () => Inertia::render('Auth/LinkSent'))
            ->name('login.link-sent');
        Route::get('login-invalid', fn () => Inertia::render('Auth/LinkInvalid'))
            ->name('login.invalid');
        Route::get('access-expired', fn () => Inertia::render('Auth/AccessExpired'))
            ->name('access.expired');
    });
