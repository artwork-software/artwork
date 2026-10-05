<?php

use App\Http\Controllers\RoomController;
use Artwork\Core\Api\Middleware\EnsureTokenNotExpired;
use Artwork\Modules\Chat\Http\Controllers\ChatController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryArticleApiController;
use Artwork\Modules\User\Services\UserStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryCategoryApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:api', EnsureTokenNotExpired::class])->get('/user', function (Request $request) {
    return $request->user();
});


// get all timeline presets with times count
// Die Liste dient dem Import in den Ablaufplan eines Termins: gleiche Schranke wie der Import selbst.
Route::middleware('auth')->get('/timeline-presets', function (Request $request) {
    $event = \Artwork\Modules\Event\Models\Event::findOrFail($request->integer('event'));
    Gate::authorize('editTimeline', $event);
    return \Artwork\Modules\Shift\Models\ShiftPresetTimeline::withCount('times')->get();
})->name('timeline-presets.all');

Route::middleware('auth')->post('/user/set-public-key', [ChatController::class, 'setPublicKey'])->name('keypair.store');
Route::middleware('auth:sanctum')->post('/chat/store', [ChatController::class, 'storeChat'])->name('chat.store');
Route::middleware('auth:sanctum')->post('/chat/message/{message}/read', [
    ChatController::class,
    'markAsRead',
])->name('chat-system.mark-as-read');
Route::middleware('auth:sanctum')->post('/chat/messages/read', [
    ChatController::class,
    'markMultipleAsRead',
])->name('chat-system.mark-multiple-as-read');

// Präsenzstatus (Chat): nur eigener Status, gemeinsamer Chat oder Dienstplan-Sichtrecht.
Route::middleware('auth')->get('/user-status/{id}', function (
    int $id,
    Request $request,
    UserStatusService $service
) {
    $user = $request->user();
    abort_unless(
        $user->id === $id
        || $user->can(\Artwork\Modules\Permission\Enums\PermissionEnum::VIEW_SHIFT_PLAN->value)
        || $user->chats()->whereHas('users', static fn ($query) => $query->whereKey($id))->exists(),
        403
    );
    return response()->json(['status' => $service->getStatus($id)]);
})->whereNumber('id')->name('user-status.show');

Route::get('/inventory/categories', [
    \Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController::class,
    'getAllCategories',
])
    ->middleware('auth')
    ->name('inventory.categories.get-all');

Route::post('/room/search', [RoomController::class, 'search'])
    ->middleware('auth')
    ->name('room.search');


Route::post('/inventory/article/search', [
    \Artwork\Modules\Inventory\Http\Controllers\InventoryArticleController::class,
    'search',
])
    ->middleware('auth')
    ->name('inventory.articles.search');


// Inventory API routes
//
// DEPRECATED: unversioniert. Nachfolger ist /api/v1/inventory* in routes/api_v1.php; diese Pfade
// verschwinden, sobald alle Verbraucher umgestellt sind.
//
// Ab hier scope-pflichtig: Tokens, die vor Einführung der Scopes ausgegeben wurden, tragen eine
// leere Scope-Menge im signierten JWT und lassen sich nicht nachrüsten — sie müssen neu erstellt
// werden. Bewusst ein eigener Deploy, damit dafür ein Zeitfenster bleibt.
Route::middleware(['auth:api', EnsureTokenNotExpired::class, CheckToken::using('inventory:read')])->group(function (): void {
    Route::get('/inventory', [InventoryCategoryApiController::class, 'index']);
    Route::get('/inventory/articles', [InventoryArticleApiController::class, 'index']);
    Route::get('/inventory/articles/{article}', [InventoryArticleApiController::class, 'show']);
});

// Shift Rules API routes
// Rechte wie bei den gleichnamigen Web-Routen (shift-rule-violations.resolve/.ignore): Verstöße
// bearbeiten darf, wer Schichten planen darf. Vorher lief hier jeder eingeloggte User durch.
Route::middleware(['auth', 'can:can plan shifts'])->group(function () {
    Route::post('/shift-rules/validate', [ShiftRuleController::class, 'validateRules'])->name('api.shift-rules.validate');
    Route::get('/shift-rules/pending', [ShiftRuleController::class, 'getPendingViolations'])->name('api.shift-rules.pending');
    Route::patch('/shift-rules/violations/{violationId}/status', [ShiftRuleController::class, 'updateViolationStatus'])->name('api.shift-rules.update-status');
});
