<?php

use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalComponentValueController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmContactListController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalDashboardController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalLoginController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalProjectFileController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalProjectTabController;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalTabSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('external')
    ->prefix('external')
    ->name('external.')
    ->group(function (): void {
        Route::post('logout', [ExternalLoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', [ExternalDashboardController::class, 'show'])->name('dashboard');
        // Eigene CRM-Daten: nur solange der CRM-Zugang (crm_access_expires_at) aktiv ist.
        Route::middleware('external.crm')->group(function (): void {
            Route::get('crm', [ExternalCrmController::class, 'show'])->name('crm.show');
            Route::get('crm/edit', [ExternalCrmController::class, 'edit'])->name('crm.edit');
            Route::post('crm/submit', [ExternalCrmController::class, 'submit'])
                ->middleware('throttle:external')
                ->name('crm.submit');
            Route::get('crm/submission-status', [ExternalCrmController::class, 'submissionStatus'])
                ->name('crm.submission-status');
        });

        Route::get('projects/{project}/tabs/{tab}', [ExternalProjectTabController::class, 'show'])
            ->middleware('external.scoped:read')
            ->name('project.tab.show');

        Route::patch('projects/{project}/tabs/{tab}/components/{component}/value', [
            ExternalComponentValueController::class,
            'update',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.component.update');

        // Dokumente im freigegebenen Tab (Dokument-Komponente)
        Route::get('projects/{project}/tabs/{tab}/components/{component}/documents', [
            ExternalProjectFileController::class,
            'index',
        ])
            ->middleware('external.scoped:read')
            ->name('project.tab.documents.index');
        Route::post('projects/{project}/tabs/{tab}/components/{component}/documents', [
            ExternalProjectFileController::class,
            'store',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.documents.store');
        Route::get('projects/{project}/tabs/{tab}/documents/{file}', [
            ExternalProjectFileController::class,
            'download',
        ])
            ->middleware('external.scoped:read')
            ->whereNumber('file')
            ->name('project.tab.documents.download');
        Route::delete('projects/{project}/tabs/{tab}/documents/{file}', [
            ExternalProjectFileController::class,
            'destroy',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->whereNumber('file')
            ->name('project.tab.documents.destroy');

        // CRM-Kontaktliste im freigegebenen Tab (eigene Kontakte anlegen/bearbeiten/entfernen)
        Route::get('projects/{project}/tabs/{tab}/components/{component}/crm-contacts', [
            ExternalCrmContactListController::class,
            'index',
        ])
            ->middleware('external.scoped:read')
            ->name('project.tab.crm-contacts.index');
        Route::get('projects/{project}/tabs/{tab}/components/{component}/crm-contacts/mask', [
            ExternalCrmContactListController::class,
            'mask',
        ])
            ->middleware('external.scoped:write')
            ->name('project.tab.crm-contacts.mask');
        Route::post('projects/{project}/tabs/{tab}/components/{component}/crm-contacts', [
            ExternalCrmContactListController::class,
            'store',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.crm-contacts.store');
        Route::patch('projects/{project}/tabs/{tab}/components/{component}/crm-contacts/{crmContact}', [
            ExternalCrmContactListController::class,
            'update',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.crm-contacts.update');
        Route::delete('projects/{project}/tabs/{tab}/components/{component}/crm-contacts/{crmContact}', [
            ExternalCrmContactListController::class,
            'destroy',
        ])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.crm-contacts.destroy');

        Route::post('projects/{project}/tabs/{tab}/submit', [ExternalTabSubmissionController::class, 'store'])
            ->middleware(['external.scoped:write', 'throttle:external'])
            ->name('project.tab.submit');
    });
