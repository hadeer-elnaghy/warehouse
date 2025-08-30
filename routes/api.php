<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\StockTransferController;

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

Route::get('test', function(){
    return response()->json([
        'success' => true,
        'message' => 'Test successful',
    ]);
});

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Warehouse routes
    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('warehouses/{id}/inventory', [WarehouseController::class, 'inventory']);

    // Inventory routes
    Route::apiResource('inventory', InventoryController::class);
    Route::get('low-stock-items', [InventoryController::class, 'lowStock']);

    // Stock transfer routes
    Route::apiResource('stock-transfers', StockTransferController::class);
    Route::post('stock-transfers/{id}/execute', [StockTransferController::class, 'execute']);
    Route::post('stock-transfers/{id}/cancel', [StockTransferController::class, 'cancel']);
    Route::get('stock-transfers-statistics', [StockTransferController::class, 'statistics']);
}); 