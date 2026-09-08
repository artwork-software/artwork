<?php

use Artwork\Modules\AppApi\Http\Controllers\AppAuthController;
use Artwork\Modules\AppApi\Http\Controllers\AppCalendarController;
use Artwork\Modules\AppApi\Http\Controllers\AppDashboardController;
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

Route::post('auth/login', [AppAuthController::class, 'login'])
    ->middleware('throttle:app-login')
    ->name('auth.login');

Route::middleware(['auth:api', 'throttle:api-token', CheckToken::using('app')])->group(function (): void {
    Route::get('me', [AppAuthController::class, 'me'])->name('me');
    Route::post('auth/logout', [AppAuthController::class, 'logout'])->name('auth.logout');

    Route::get('dashboard', [AppDashboardController::class, 'show'])->name('dashboard');
    Route::get('shift-plan', [AppShiftPlanController::class, 'show'])->name('shift-plan');
    Route::get('shift-plan/shifts/{shift}', [AppShiftPlanController::class, 'showShift'])->name('shift-plan.shift');
    Route::get('calendar', [AppCalendarController::class, 'show'])->name('calendar');
    Route::get('shift-list', [AppShiftListController::class, 'show'])->name('shift-list');

    Route::get('projects', [AppProjectController::class, 'index'])->name('projects');

    Route::prefix('projects/{project}')->name('projects.')->group(function (): void {
        Route::get('', [AppProjectController::class, 'show'])->name('show');
        Route::get('tabs/{projectTab}', [AppProjectController::class, 'showTab'])->name('tab');
        Route::patch('tabs/{projectTab}/components/{component}', [AppProjectComponentValueController::class, 'update'])
            ->name('component.update');
        Route::post('comments', [AppProjectCommentController::class, 'store'])->name('comments.store');
        Route::patch('tasks/{task}/done', [AppProjectTaskController::class, 'toggleDone'])->name('tasks.toggle-done');
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
