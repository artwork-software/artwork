<?php

use App\Http\Controllers\RoomController;
use Artwork\Modules\Chat\Http\Controllers\ChatController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryArticleApiController;
use Artwork\Modules\User\Services\UserStatusService;
use Illuminate\Http\Request;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// get all timeline presets with times count
Route::middleware('auth:sanctum')->get('/timeline-presets', function () {
    return \Artwork\Modules\Shift\Models\ShiftPresetTimeline::withCount('times')->get();
})->name('timeline-presets.all');


Route::middleware('auth:sanctum')->post('/chat/store', [ChatController::class, 'storeChat'])->name('chat.store');
Route::middleware('auth:sanctum')->post('/chat/message/{message}/read', [
    ChatController::class,
    'markAsRead',
])->name('chat-system.mark-as-read');
Route::middleware('auth:sanctum')->post('/chat/messages/read', [
    ChatController::class,
    'markMultipleAsRead',
])->name('chat-system.mark-multiple-as-read');

// Präsenzstatus: Frontend nutzt den Endpunkt nur im Chat (PopupChat → useUserStatus) für den
// Chat-Partner. Sicherheits-Audit 21.09.2026 (C, NIEDRIG): nur eigener Status, gemeinsamer Chat
// oder Dienstplan-Sichtrecht (Anwesenheit ist dort ohnehin sichtbar).
Route::middleware('auth:sanctum')->get('/user-status/{id}', function (
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
    ->middleware('auth:sanctum')
    ->name('inventory.categories.get-all');

Route::post('/room/search', [RoomController::class, 'search'])
    ->middleware('auth:sanctum')
    ->name('room.search');


Route::post('/inventory/article/search', [
    \Artwork\Modules\Inventory\Http\Controllers\InventoryArticleController::class,
    'search',
])
    ->middleware('auth:sanctum')
    ->name('inventory.articles.search');


// Inventory API routes
//
// DEPRECATED: unversioniert. Nachfolger ist /api/v1/inventory* in routes/api_v1.php; diese Pfade
// verschwinden, sobald alle Verbraucher umgestellt sind.
//
// Ab hier scope-pflichtig: Tokens, die vor Einführung der Scopes ausgegeben wurden, tragen eine
// leere Scope-Menge im signierten JWT und lassen sich nicht nachrüsten — sie müssen neu erstellt
// werden. Bewusst ein eigener Deploy, damit dafür ein Zeitfenster bleibt.
Route::middleware(['auth:api', CheckToken::using('inventory:read')])->group(function (): void {
    Route::get('/inventory', [InventoryCategoryApiController::class, 'index']);
    Route::get('/inventory/articles', [InventoryArticleApiController::class, 'index']);
    Route::get('/inventory/articles/{article}', [InventoryArticleApiController::class, 'show']);
});
