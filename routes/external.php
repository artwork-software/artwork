<?php

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalDashboardController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('external')
    ->prefix('external')
    ->name('external.')
    ->group(function (): void {
        Route::post('logout', [ExternalLoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', [ExternalDashboardController::class, 'show'])->name('dashboard');
        Route::get('crm', [ExternalCrmController::class, 'show'])->name('crm.show');
    });
