<?php

use Artwork\Modules\AiAssistant\Http\Controllers\DevStubController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Entwicklungs-Stub der KI-Bar (core-ki-bar-ui, Konzept C4 §9)
|--------------------------------------------------------------------------
|
| Registriert wird diese Datei ausschliesslich in der Umgebung `local`
| (app/Providers/RouteServiceProvider.php). Sie ersetzt waehrend der Entwicklung den
| artwork-ai-Dienst und streamt feste Event-Skripte nach Protokoll 1.
|
| Bewusst ohne die `web`-Gruppe: die Bar spricht artwork-ai spaeter cross-origin an, schickt also
| weder Session-Cookie noch CSRF-Token (0.3 §10). Mit VerifyCsrfToken wuerde der Stub genau die
| Requests ablehnen, die im Betrieb richtig sind. Autorisiert wird — wie bei artwork-ai — ueber
| den Authorization-Header, den der Stub nur auf Anwesenheit prueft.
|
*/

Route::post('/ai/dev-stub/chat', [DevStubController::class, 'chat'])
    ->name('ai.dev-stub.chat');

Route::post('/ai/dev-stub/actions/{action}/confirm', [DevStubController::class, 'confirm'])
    ->name('ai.dev-stub.confirm');

// Aufrufzaehler je client_turn_id — Nachweis fuer den Dusk-Test, dass nach `token_expired` genau
// einmal mit derselben Turn-ID wiederholt wird (core-ki-bar-stub-test).
Route::get('/ai/dev-stub/calls/{clientTurnId}', [DevStubController::class, 'calls'])
    ->name('ai.dev-stub.calls');
