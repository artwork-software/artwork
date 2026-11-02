<?php

use Artwork\Modules\AppApi\Http\Controllers\AppAuthController;
use Artwork\Modules\AppApi\Http\Controllers\AppCalendarController;
use Artwork\Modules\AppApi\Http\Controllers\AppDashboardController;
use Artwork\Modules\AppApi\Http\Controllers\AppInventoryController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectCommentController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectComponentValueController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectEventController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectFileController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectShiftController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectShiftWorkerController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectTaskController;
use Artwork\Modules\AppApi\Http\Controllers\AppProjectTeamController;
use Artwork\Modules\AppApi\Http\Controllers\AppShiftListController;
use Artwork\Modules\AppApi\Http\Controllers\AppShiftPlanController;
use Artwork\Modules\AppApi\Http\Controllers\AppTicketingController;
use Artwork\Modules\AppApi\Http\Middleware\EnsureAppModuleEnabled;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| App API Routes (v1)
|--------------------------------------------------------------------------
|
| Consumed by the artwork app. Response shapes are validated on the device
| and by AppContractTest against artwork/Modules/AppApi/openapi.yaml —
| change spec and implementation together. Tokens are Passport personal
| access tokens (auth:api guard) carrying the "app" scope; that scope is
| what keeps machine keys out of here and device tokens out of /api/v1.
|
*/

Route::post('auth/discover', [AppAuthController::class, 'discover'])
    ->middleware('throttle:app-discover')
    ->name('auth.discover');

Route::post('auth/login', [AppAuthController::class, 'login'])
    ->middleware('throttle:app-login')
    ->name('auth.login');

Route::middleware(['auth:api', 'throttle:api-token', CheckToken::using('app')])->group(function (): void {
    Route::get('me', [AppAuthController::class, 'me'])->name('me');
    Route::post('auth/logout', [AppAuthController::class, 'logout'])->name('auth.logout');

    Route::get('dashboard', [AppDashboardController::class, 'show'])->name('dashboard');
    // Modul-Schalter wie im Web (ModuleSettingsMiddleware): abgeschaltete Module antworten 403
    Route::middleware(EnsureAppModuleEnabled::class . ':shift_plan')->group(function (): void {
        Route::get('shift-plan', [AppShiftPlanController::class, 'show'])->name('shift-plan');
        Route::get('shift-plan/shifts/{shift}', [AppShiftPlanController::class, 'showShift'])
            ->name('shift-plan.shift');
        Route::get('shift-list', [AppShiftListController::class, 'show'])->name('shift-list');
    });
    Route::get('calendar', [AppCalendarController::class, 'show'])
        ->middleware(EnsureAppModuleEnabled::class . ':room_assignment')
        ->name('calendar');

    // Der Einlass von Artwork-Tickets; tickets bleibt die Quelle, artwork reicht durch.
    Route::prefix('ticketing/dates')->name('ticketing.')->group(function (): void {
        Route::get('', [AppTicketingController::class, 'dates'])->name('dates');
        Route::get('{event}', [AppTicketingController::class, 'show'])->name('dates.show');
        Route::post('{event}/check-ins', [AppTicketingController::class, 'checkIn'])->name('check-ins');
        Route::post('{event}/check-ins/revert', [AppTicketingController::class, 'revert'])
            ->name('check-ins.revert');
        Route::get('{event}/passes', [AppTicketingController::class, 'passes'])->name('passes');
        Route::post('{event}/pass-admissions', [AppTicketingController::class, 'admit'])
            ->name('pass-admissions');
    });

    // Inventar zum Nachschlagen vor Ort; Ansehen darf wie im Web jede*r, solange das Modul an ist.
    Route::prefix('inventory')
        ->middleware(EnsureAppModuleEnabled::class . ':inventory')
        ->name('inventory.')
        ->group(function (): void {
            Route::get('categories', [AppInventoryController::class, 'categories'])->name('categories');
            Route::get('articles', [AppInventoryController::class, 'index'])->name('articles');
            Route::get('articles/{article}', [AppInventoryController::class, 'show'])->name('articles.show');
            Route::get('articles/{article}/availability', [AppInventoryController::class, 'availability'])
                ->name('articles.availability');
        });

    Route::get('projects', [AppProjectController::class, 'index'])
        ->middleware(EnsureAppModuleEnabled::class . ':projects')
        ->name('projects');

    Route::prefix('projects/{project}')
        ->middleware(EnsureAppModuleEnabled::class . ':projects')
        ->name('projects.')
        ->group(function (): void {
            Route::get('', [AppProjectController::class, 'show'])->name('show');
            Route::get('tabs/{projectTab}', [AppProjectController::class, 'showTab'])->name('tab');
            Route::patch(
                'tabs/{projectTab}/components/{component}',
                [AppProjectComponentValueController::class, 'update'],
            )->name('component.update');
            Route::post('comments', [AppProjectCommentController::class, 'store'])->name('comments.store');
            Route::patch('tasks/{task}/done', [AppProjectTaskController::class, 'toggleDone'])
                ->name('tasks.toggle-done');
            Route::get('team/candidates', [AppProjectTeamController::class, 'candidates'])->name('team.candidates');
            Route::post('team', [AppProjectTeamController::class, 'store'])->name('team.store');

            // Child bindings are resolved through the project's relations — an
            // event, shift, checklist or member of another project is a 404.
            Route::scopeBindings()->group(function (): void {
                Route::post('events', [AppProjectEventController::class, 'store'])->name('events.store');
                Route::get('events/{event}', [AppProjectEventController::class, 'show'])->name('events.show');
                Route::patch('events/{event}', [AppProjectEventController::class, 'update'])->name('events.update');

                Route::post('shifts', [AppProjectShiftController::class, 'store'])->name('shifts.store');
                Route::get('shifts/{shift}', [AppProjectShiftController::class, 'show'])->name('shifts.show');
                Route::patch('shifts/{shift}', [AppProjectShiftController::class, 'update'])->name('shifts.update');
                Route::delete('shifts/{shift}', [AppProjectShiftController::class, 'destroy'])->name('shifts.destroy');
                Route::get('shifts/{shift}/workers', [AppProjectShiftWorkerController::class, 'index'])
                    ->name('shifts.workers');
                Route::post('shifts/{shift}/workers', [AppProjectShiftWorkerController::class, 'store'])
                    ->name('shifts.workers.store');
                Route::delete('shifts/{shift}/workers', [AppProjectShiftWorkerController::class, 'destroy'])
                    ->name('shifts.workers.destroy');

                Route::post('checklists/{checklist}/tasks', [AppProjectTaskController::class, 'store'])
                    ->name('tasks.store');

                Route::patch('team/{user}', [AppProjectTeamController::class, 'update'])->name('team.update');
                Route::delete('team/{user}', [AppProjectTeamController::class, 'destroy'])->name('team.destroy');
            });
        });
});

// Signed download links generated in the project tab payloads — the signature
// is the gate; the device opens these in a browser without the API token.
Route::get('files/{projectFile}', AppProjectFileController::class)
    ->middleware('signed')
    ->name('files.download');
