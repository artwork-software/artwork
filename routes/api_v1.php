<?php

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

Route::middleware(['auth:api', 'throttle:machine-api'])->group(function (): void {
    Route::middleware(CheckToken::using('inventory:read'))->group(function (): void {
        Route::get('/inventory', [InventoryCategoryApiController::class, 'index'])
            ->name('api.v1.inventory.index');

        Route::get('/inventory/articles', [InventoryArticleApiController::class, 'index'])
            ->name('api.v1.inventory.articles.index');

        Route::get('/inventory/articles/{article}', [InventoryArticleApiController::class, 'show'])
            ->name('api.v1.inventory.articles.show');
    });
});
