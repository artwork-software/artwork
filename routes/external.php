<?php

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalComponentValueController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalDashboardController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalLoginController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalProjectTabController;
use Illuminate\Support\Facades\Route;

Route::middleware('external')
    ->prefix('external')
    ->name('external.')
    ->group(function (): void {
        Route::post('logout', [ExternalLoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', [ExternalDashboardController::class, 'show'])->name('dashboard');
        Route::get('crm', [ExternalCrmController::class, 'show'])->name('crm.show');
        Route::get('crm/edit', [ExternalCrmController::class, 'edit'])->name('crm.edit');
        Route::post('crm/submit', [ExternalCrmController::class, 'submit'])->name('crm.submit');
        Route::get('crm/submission-status', [ExternalCrmController::class, 'submissionStatus'])
            ->name('crm.submission-status');

        Route::get('projects/{project}/tabs/{tab}', [ExternalProjectTabController::class, 'show'])
            ->middleware('external.scoped:read')
            ->name('project.tab.show');

        Route::patch('projects/{project}/tabs/{tab}/components/{component}/value', [
            ExternalComponentValueController::class,
            'update',
        ])
            ->middleware('external.scoped:write')
            ->name('project.tab.component.update');
    });
