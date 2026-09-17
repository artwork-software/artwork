<?php

use Artwork\Core\Api\Middleware\EnsureTokenNotExpired;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiActionApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiContextApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiConversationApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiEventApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiEventWriteApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiInventoryApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiLookupApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiProjectApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\Api\AiShiftApiController;
use Artwork\Modules\AiAssistant\Http\Controllers\JwksController;
use Artwork\Modules\AiAssistant\Http\Middleware\EnsureAiWriteScope;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryArticleApiController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryCategoryApiController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| Machine API v1
|--------------------------------------------------------------------------
|
| Versionierte Schnittstelle für externe Systeme (derzeit: der Ticketshop).
| Registriert in RouteServiceProvider mit Präfix api/v1 und der Middleware-Gruppe api.machine.
|
| Reihenfolge der Middleware ist bedeutsam: auth:api muss vor throttle:machine-api und vor der
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

Route::middleware(['auth:api', EnsureTokenNotExpired::class, 'throttle:machine-api'])->group(function (): void {
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
});
