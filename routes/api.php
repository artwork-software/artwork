<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// search timeline preset timeline-preset.search
Route::get('/timeline-preset/search', [
    \App\Http\Controllers\TimelinePresetController::class,
    'search'
])->name('timeline-preset.search');


Route::get('/generate-avatar-image/{letters}', [\App\Http\Controllers\UserController::class, 'createAvatarImage'])
    ->name('generate-avatar-image');


Route::middleware('auth:sanctum')->post('/user/set-public-key', function (Request $request) {
    $request->validate(['chat_public_key' => 'required|string']);
    $user = $request->user();
    $user->chat_public_key = $request->chat_public_key;
    $user->save();

    //return response()->json(['status' => 'ok']);
})->name('keypair.store');