<?php

use Artwork\Core\Api\Middleware\EnsureAiAccessPermission;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiActionApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiContextApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiConversationApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiEventApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiEventWriteApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiInventoryApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiLookupApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiPeopleApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiProjectApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiScenarioApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiScenarioFourApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiScenarioFourReadApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiShiftApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiShiftInsightsApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiShiftPlanningApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\JwksController;
use Artwork\Modules\AiAssistant\Http\Middleware\EnsureAiWriteScope;
use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Artwork\Modules\Event\Http\Controllers\Api\EventApiController;
use Artwork\Modules\Event\Http\Controllers\Api\EventStatusApiController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryArticleApiController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryCategoryApiController;
use Artwork\Modules\Room\Http\Controllers\Api\RoomApiController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| Machine API v1
|--------------------------------------------------------------------------
|
| Versionierte Schnittstelle für externe Systeme (derzeit: Ticketshop, Inventar, Raumbelegung).
| Registriert in RouteServiceProvider mit Präfix api/v1 und der Middleware-Gruppe api.machine.
|
| Reihenfolge der Middleware ist bedeutsam: auth:api muss vor throttle:api-token und vor der
| Scope-Prüfung laufen, sonst ist der Token beim Bilden des Rate-Limit-Schlüssels noch nicht
| aufgelöst und das Limit fiele auf die IP zurück.
|
| Passport registriert keine Middleware-Aliase; CheckToken::using() erzeugt die Angabe mitsamt
| Parameter. Dass Scope-Namen einen Doppelpunkt enthalten, ist unkritisch — Laravel trennt
| Middleware-Name und Parameter am ersten Doppelpunkt.
|
*/

// JWKS: bewusst ohne auth:api — der Schlüssel ist öffentlich, und artwork-ai muss ihn holen können,
// bevor es ein Token verifizieren kann. ApiAccessLog schreibt hier nichts (kein Bearer-Header).
Route::middleware('throttle:60,1')
    ->get('/ai/jwks', JwksController::class)
    ->name('api.v1.ai.jwks');

// EnsureAiAccessPermission direkt hinter auth:api: zusätzliche, nur per Konsole vergebbare Schranke
// (ConsoleOnlyPermissionEnum::AiAccess) für alle Maschinen- und KI-Routen.
Route::middleware(['auth:api', EnsureAiAccessPermission::class, 'throttle:api-token'])->group(function (): void {
    // Chat-Historie fuer artwork-ai. Der Token traegt immer den echten User; jede Abfrage ist auf
    // dessen Gespraeche eingeschraenkt, eine fremde ID liefert 404 statt 403.
    Route::middleware(CheckToken::using('ai:transcript'))->group(function (): void {
        Route::post('/ai/conversations', [AiConversationApiController::class, 'store'])
            ->name('api.v1.ai.conversations.store');

        Route::get('/ai/conversations/{conversation}', [AiConversationApiController::class, 'show'])
            ->name('api.v1.ai.conversations.show');

        Route::post('/ai/conversations/{conversation}/turns', [AiConversationApiController::class, 'storeTurn'])
            ->name('api.v1.ai.conversations.turns.store');
    });

    // Lese-Endpunkte fuer artwork-ai (Stufe B, Konzept B1 §3). Der Scope ai:read wird nur
    // ausgegeben, wenn das Haus Stufe B eingeschaltet hat (AiSessionService); jede Abfrage laeuft
    // zusaetzlich ueber visibleFor($user) — der Token traegt immer den echten Benutzer.
    Route::middleware(CheckToken::using('ai:read'))->group(function (): void {
        Route::get('/ai/context', AiContextApiController::class)
            ->name('api.v1.ai.context');

        Route::get('/ai/projects', [AiProjectApiController::class, 'index'])
            ->name('api.v1.ai.projects.index');

        Route::get('/ai/projects/{project}', [AiProjectApiController::class, 'show'])
            ->whereNumber('project')
            ->name('api.v1.ai.projects.show');

        Route::get('/ai/events', [AiEventApiController::class, 'index'])
            ->name('api.v1.ai.events.index');

        // Belegung und freie Slots rechnen bewusst mit *allen* Terminen der Raeume: ein belegter
        // Raum ist ein Faktum, der Termininhalt bleibt geschuetzt (B1 §5).
        Route::get('/ai/rooms/occupancy', [AiEventApiController::class, 'occupancy'])
            ->name('api.v1.ai.rooms.occupancy');

        Route::get('/ai/rooms/free-slots', [AiEventApiController::class, 'freeSlots'])
            ->name('api.v1.ai.rooms.free-slots');

        // Eigener Einsatzplan: kein Parameter fuer fremde Personen (B1 §6). Der Dienstplan-
        // Ausschnitt verlangt zusaetzlich das Recht "Schichtplan einsehen" wie die Oberflaeche.
        Route::get('/ai/me/schedule', [AiShiftApiController::class, 'schedule'])
            ->name('api.v1.ai.me.schedule');

        Route::get('/ai/shift-plan', [AiShiftApiController::class, 'shiftPlan'])
            ->name('api.v1.ai.shift-plan');

        // Verfuegbarkeit rechnet mit *allen* Ausgaben des Zeitraums; Projektnamen gehen nur raus,
        // wo das Projekt fuer die fragende Person sichtbar ist (B1 §5, gleiche Trennung wie beim Raum).
        Route::get('/ai/inventory/availability', AiInventoryApiController::class)
            ->name('api.v1.ai.inventory.availability');

        // Nachschlagen von Stammdaten (core-api-lookups): ohne diese beiden Listen kann das Modell
        // einen Raum- oder Terminartnamen nicht in die IDs aufloesen, die die Schreib-Endpunkte
        // verlangen. Bewusst ohne Zeitraum — es sind keine Aggregationen.
        Route::get('/ai/rooms', [AiLookupApiController::class, 'rooms'])
            ->name('api.v1.ai.rooms.index');

        Route::get('/ai/event-types', [AiLookupApiController::class, 'eventTypes'])
            ->name('api.v1.ai.event-types.index');

        // Schichtplanung (ai-shift-planning): nur mit "Schichten planen", Gewerks-Scope des Planers.
        // Der Vorschlag ist ein POST, schreibt aber nichts (Transaktion mit Rollback, ohne Events).
        Route::get('/ai/people', [AiPeopleApiController::class, 'index'])
            ->name('api.v1.ai.people.index');

        Route::get('/ai/people/{type}/{id}/workload', [AiPeopleApiController::class, 'workload'])
            ->whereIn('type', AiPeopleService::TYPES)
            ->whereNumber('id')
            ->name('api.v1.ai.people.workload');

        Route::post('/ai/shift-plan/suggest', [AiShiftPlanningApiController::class, 'suggest'])
            ->name('api.v1.ai.shift-plan.suggest');

        Route::get('/ai/events/{event}/shifts', [AiShiftApiController::class, 'eventShifts'])
            ->whereNumber('event')
            ->name('api.v1.ai.events.shifts');

        Route::get('/ai/shifts/open', [AiShiftInsightsApiController::class, 'openShifts'])
            ->name('api.v1.ai.shifts.open');

        Route::get('/ai/shifts/{shift}/candidates', [AiShiftInsightsApiController::class, 'candidates'])
            ->whereNumber('shift')
            ->name('api.v1.ai.shifts.candidates');

        Route::get('/ai/shift-rule-violations', [AiShiftInsightsApiController::class, 'violations'])
            ->name('api.v1.ai.shift-rule-violations.index');

        Route::get('/ai/crafts', [AiShiftInsightsApiController::class, 'crafts'])
            ->name('api.v1.ai.crafts.index');

        Route::get('/ai/crafts/{craft}/capacity', [AiShiftInsightsApiController::class, 'capacity'])
            ->whereNumber('craft')
            ->name('api.v1.ai.crafts.capacity');

        // Alltagsszenarien (ai-scenarios-3 S1–S7).
        Route::get('/ai/day-overview', [AiScenarioApiController::class, 'dayOverview'])
            ->name('api.v1.ai.day-overview');

        Route::get('/ai/projects/{project}/budget-summary', [AiScenarioApiController::class, 'budgetSummary'])
            ->whereNumber('project')
            ->name('api.v1.ai.projects.budget-summary');

        Route::post('/ai/shift-plan/staff-event', [AiScenarioApiController::class, 'staffEvent'])
            ->name('api.v1.ai.shift-plan.staff-event');

        Route::get('/ai/absences', [AiScenarioApiController::class, 'absences'])
            ->name('api.v1.ai.absences.index');

        Route::get('/ai/me/tasks', [AiScenarioApiController::class, 'myTasks'])
            ->name('api.v1.ai.me.tasks');

        Route::get('/ai/rooms/conflicts', [AiScenarioApiController::class, 'roomConflicts'])
            ->name('api.v1.ai.rooms.conflicts');

        Route::get('/ai/people/{type}/{id}/work-time', [AiScenarioApiController::class, 'workTime'])
            ->whereIn('type', AiPeopleService::TYPES)
            ->whereNumber('id')
            ->name('api.v1.ai.people.work-time');

        // Ausfall ersetzen — Vorschlag (ai-scenarios-4 S9), schreibt nichts.
        Route::post('/ai/shift-plan/replacements', [AiScenarioFourApiController::class, 'replacements'])
            ->name('api.v1.ai.shift-plan.replacements');

        // Auswertungen und Lookups (ai-scenarios-4 S10/S13/S14–S18).
        Route::post('/ai/people/common-free-slots', [AiScenarioFourReadApiController::class, 'commonFreeSlots'])
            ->name('api.v1.ai.people.common-free-slots');

        Route::get('/ai/contracts', [AiScenarioFourReadApiController::class, 'contracts'])
            ->name('api.v1.ai.contracts.index');

        Route::get('/ai/work-time/overview', [AiScenarioFourReadApiController::class, 'workTimeOverview'])
            ->name('api.v1.ai.work-time.overview');

        Route::get('/ai/rooms/utilization', [AiScenarioFourReadApiController::class, 'roomUtilization'])
            ->name('api.v1.ai.rooms.utilization');

        Route::get('/ai/checklists', [AiLookupApiController::class, 'checklists'])
            ->name('api.v1.ai.checklists.index');

        Route::get('/ai/qualifications', [AiLookupApiController::class, 'qualifications'])
            ->name('api.v1.ai.qualifications.index');

        Route::get('/ai/project-states', [AiLookupApiController::class, 'projectStates'])
            ->name('api.v1.ai.project-states.index');
    });

    // Schreib-Endpunkte (Stufe C, Konzept W1). Statt CheckToken steht hier EnsureAiWriteScope:
    // die Vorschau (dry_run=true) darf auch ein Read-Token holen, die Ausfuehrung verlangt
    // ai:write — und das gibt es nur mit einer bestaetigten Aktion (W2 §2).
    Route::middleware(EnsureAiWriteScope::class)->group(function (): void {
        Route::post('/ai/events/batch', [AiEventWriteApiController::class, 'storeBatch'])
            ->name('api.v1.ai.events.batch.store');

        Route::patch('/ai/events/{event}', [AiEventWriteApiController::class, 'update'])
            ->whereNumber('event')
            ->name('api.v1.ai.events.update');

        Route::delete('/ai/events/batch', [AiEventWriteApiController::class, 'destroyBatch'])
            ->name('api.v1.ai.events.batch.destroy');

        Route::post('/ai/shift-assignments', [AiShiftPlanningApiController::class, 'assign'])
            ->name('api.v1.ai.shift-assignments.store');

        Route::post('/ai/shift-assignments/remove', [AiShiftPlanningApiController::class, 'unassign'])
            ->name('api.v1.ai.shift-assignments.remove');

        Route::post('/ai/shift-assignments/bulk', [AiScenarioApiController::class, 'bulkAssign'])
            ->name('api.v1.ai.shift-assignments.bulk');

        Route::patch('/ai/tasks/{task}', [AiScenarioApiController::class, 'updateTask'])
            ->whereNumber('task')
            ->name('api.v1.ai.tasks.update');

        // ai-scenarios-4 S9–S13.
        Route::post('/ai/shift-assignments/replace', [AiScenarioFourApiController::class, 'replace'])
            ->name('api.v1.ai.shift-assignments.replace');

        Route::post('/ai/tasks', [AiScenarioFourApiController::class, 'storeTask'])
            ->name('api.v1.ai.tasks.store');

        Route::post('/ai/absences', [AiScenarioFourApiController::class, 'storeAbsence'])
            ->name('api.v1.ai.absences.store');

        Route::patch('/ai/events/batch', [AiScenarioFourApiController::class, 'updateEvents'])
            ->name('api.v1.ai.events.batch.update');

        Route::post('/ai/shifts', [AiScenarioFourApiController::class, 'storeShifts'])
            ->name('api.v1.ai.shifts.store');

        Route::post('/ai/shifts/copy', [AiScenarioFourApiController::class, 'copyShifts'])
            ->name('api.v1.ai.shifts.copy');
    });

    // Die bestaetigte Aktion selbst — artwork-ai holt sie im Confirm-Turn (W2 §4). Braucht ein
    // Write-Token, liefert aber nie die jti mit.
    Route::middleware(CheckToken::using('ai:write'))->group(function (): void {
        Route::get('/ai/actions/{action}', AiActionApiController::class)
            ->name('api.v1.ai.actions.show');
    });
    Route::middleware(CheckToken::using('inventory:read'))->group(function (): void {
        Route::get('/inventory', [InventoryCategoryApiController::class, 'index'])
            ->name('api.v1.inventory.index');

        Route::get('/inventory/articles', [InventoryArticleApiController::class, 'index'])
            ->name('api.v1.inventory.articles.index');

        Route::get('/inventory/articles/{article}', [InventoryArticleApiController::class, 'show'])
            ->name('api.v1.inventory.articles.show');
    });

    // Raumbelegung für externe Planungssysteme: Räume, Termine und Terminstatus, nur lesend.
    // Die Feldnamen der Antworten sind ein Vertrag — nur ergänzen, nie umbenennen.
    Route::middleware(CheckToken::using('calendar:read'))->group(function (): void {
        Route::get('/rooms', [RoomApiController::class, 'index'])
            ->name('api.v1.rooms.index');

        Route::get('/events', [EventApiController::class, 'index'])
            ->name('api.v1.events.index');

        Route::get('/event-statuses', [EventStatusApiController::class, 'index'])
            ->name('api.v1.event-statuses.index');
    });
});
