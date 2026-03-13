<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WooWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CsvController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InventoryDiffController;
use App\Http\Controllers\Api\InventoryFileController;
use App\Http\Controllers\Api\InventoryDebugController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::get('/csv/{inventory}', [CsvController::class, 'index']);
Route::post('/auth/login', [AuthController::class, 'login']);


Route::get('/inventory/{full}/delta/{delta}/diff', [InventoryDiffController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {


    Route::get(
        '/inventory-files/{id}/changes',
        [InventoryDebugController::class, 'changes']
    );

    Route::get('/inventory-files', [InventoryFileController::class, 'index']);
    Route::get('/inventory-files/{id}/content', [InventoryFileController::class, 'content']);
    Route::get('/inventory-files/{inventoryFile}', [InventoryFileController::class, 'show']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::apiResource('products', ProductController::class);
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});





Route::post('/order', [WooWebhookController::class, 'handle']);

Route::post('/order/store', [WooWebhookController::class, 'store']);

Route::get('/order/generate/{id}', [WooWebhookController::class, 'generate']);


Route::get('/test', function () {
    return response()->json(['ok' => true]);
});


