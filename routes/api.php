<?php

use App\Http\Controllers\RoomController;
use Artwork\Modules\Chat\Http\Controllers\ChatController;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryArticleApiController;
use Artwork\Modules\User\Services\UserStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Artwork\Modules\Inventory\Http\Controllers\Api\InventoryCategoryApiController;
use Artwork\Modules\Shift\Http\Controllers\ShiftRuleController;

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


// search timeline preset timeline-preset.search
Route::get('/timeline-preset/search', [
    \App\Http\Controllers\TimelinePresetController::class,
    'search'
])->name('timeline-preset.search');


Route::get('/generate-avatar-image/{letters}', [\Artwork\Modules\User\Http\Controllers\UserController::class, 'createAvatarImage'])
    ->name('generate-avatar-image');


Route::middleware('auth:sanctum')->post('/user/set-public-key', [ChatController::class, 'setPublicKey'])->name('keypair.store');
Route::middleware('auth:sanctum')->post('/chat/store', [ChatController::class, 'storeChat'])->name('chat.store');
Route::middleware('auth:sanctum')->post('/chat/message/{message}/read', [ChatController::class, 'markAsRead'])->name('chat-system.mark-as-read');
Route::middleware('auth:sanctum')->post('/chat/messages/read', [ChatController::class, 'markMultipleAsRead'])->name('chat-system.mark-multiple-as-read');

Route::get('/user-status/{id}', function ($id, UserStatusService $service) {
    return response()->json(['status' => $service->getStatus($id)]);
});

Route::get('/inventory/categories', [\Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController::class, 'getAllCategories'])
    ->name('inventory.categories.get-all');

Route::post('/room/search', [RoomController::class, 'search'])
    ->name('room.search');


Route::post('/inventory/article/search', [\Artwork\Modules\Inventory\Http\Controllers\InventoryArticleController::class, 'search'])->name('inventory.articles.search');


// Inventory API routes
Route::middleware('auth:api')->group(function () {
    Route::get('/inventory', [InventoryCategoryApiController::class, 'index']);
    Route::get('/inventory/articles', [InventoryArticleApiController::class, 'index']);
    Route::get('/inventory/articles/{article}', [InventoryArticleApiController::class, 'show']);
});

// Shift Rules API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shift-rules/validate', [ShiftRuleController::class, 'validateRules'])->name('api.shift-rules.validate');
    Route::get('/shift-rules/pending', [ShiftRuleController::class, 'getPendingViolations'])->name('api.shift-rules.pending');
    Route::patch('/shift-rules/violations/{violationId}/status', [ShiftRuleController::class, 'updateViolationStatus'])->name('api.shift-rules.update-status');
});
