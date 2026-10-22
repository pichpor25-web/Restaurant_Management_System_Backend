<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RestaurantTableController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/payments/bakong/checkout/{orderId}', [PaymentController::class, 'checkout'])
            ->whereNumber('orderId');
        Route::post('/payments/bakong/verify', [PaymentController::class, 'verifyTransaction']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::match(['put', 'patch'], '/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('/tables', [RestaurantTableController::class, 'index']);
        Route::post('/tables', [RestaurantTableController::class, 'store']);
        Route::get('/tables/{id}', [RestaurantTableController::class, 'show']);
        Route::match(['put', 'patch'], '/tables/{id}', [RestaurantTableController::class, 'update']);
        Route::delete('/tables/{id}', [RestaurantTableController::class, 'destroy']);
        Route::patch('/tables/{id}/status', [RestaurantTableController::class, 'updateStatus']);

        Route::get('/inventory', [InventoryItemController::class, 'index']);
        Route::post('/inventory', [InventoryItemController::class, 'store']);
        Route::get('/inventory/{id}', [InventoryItemController::class, 'show']);
        Route::match(['put', 'patch'], '/inventory/{id}', [InventoryItemController::class, 'update']);
        Route::delete('/inventory/{id}', [InventoryItemController::class, 'destroy']);
        Route::post('/inventory/{id}/adjust', [InventoryItemController::class, 'adjustStock']);
    });
});
