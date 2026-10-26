<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\MenuItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TakeawayOrderController;
use App\Http\Controllers\Api\DineInOrderController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController; 
// Public Authentication Endpoints


Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Authenticated Endpoints
Route::middleware('auth:sanctum')->group(function (): void {
    // User Profile & Logout
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::match(['put', 'patch'], '/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // Bakong KHQR Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/bakong/checkout/{orderId}', [PaymentController::class, 'checkout'])
        ->whereNumber('orderId');
    Route::post('/payments/bakong/verify', [PaymentController::class, 'verifyTransaction']);
    

    // Categories
    
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::match(['put', 'patch'], '/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Restaurant Tables
    Route::get('/tables', [RestaurantTableController::class, 'index']);
    Route::post('/tables', [RestaurantTableController::class, 'store']);
    Route::get('/tables/{id}', [RestaurantTableController::class, 'show']);
    Route::match(['put', 'patch'], '/tables/{id}', [RestaurantTableController::class, 'update']);
    Route::delete('/tables/{id}', [RestaurantTableController::class, 'destroy']);
    Route::patch('/tables/{id}/status', [RestaurantTableController::class, 'updateStatus']);

    // Inventory Items & Stock Adjustments
    Route::get('/inventory', [InventoryItemController::class, 'index']);
    Route::post('/inventory', [InventoryItemController::class, 'store']);
    Route::put('/inventory/{id}', [InventoryItemController::class, 'update']);
    Route::delete('/inventory/{id}', [InventoryItemController::class, 'destroy']);
    
    // សម្រាប់ការកែសម្រួល Stock (Stock In / Out)
    Route::post('/inventory/{id}/adjust', [InventoryItemController::class, 'adjustStock']);

        // Menu Items
   
    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::get('/menu-items/{id}', [MenuItemController::class, 'show']);
    Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);

    // Order Items
    Route::get('/order-items', [OrderItemController::class, 'index']);
    Route::post('/order-items', [OrderItemController::class, 'store']);
    Route::get('/order-items/{id}', [OrderItemController::class, 'show']);
    Route::put('/order-items/{id}', [OrderItemController::class, 'update']);
    Route::delete('/order-items/{id}', [OrderItemController::class, 'destroy']);

    
// Route For Take_away //

Route::get('/takeaway-orders', [TakeawayOrderController::class, 'index']);
Route::post('/takeaway-orders', [TakeawayOrderController::class, 'store']);
Route::get('/takeaway-orders/{id}', [TakeawayOrderController::class, 'show']);
Route::put('/takeaway-orders/{id}', [TakeawayOrderController::class, 'update']);
Route::delete('/takeaway-orders/{id}', [TakeawayOrderController::class, 'destroy']);
//Route For Dine_In//

Route::get('/dine-in-orders', [DineInOrderController::class, 'index']);
Route::post('/dine-in-orders', [DineInOrderController::class, 'store']);
Route::get('/dine-in-orders/{id}', [DineInOrderController::class, 'show']);
Route::put('/dine-in-orders/{id}', [DineInOrderController::class, 'update']);
Route::delete('/dine-in-orders/{id}', [DineInOrderController::class, 'destroy']);

   Route::get('/reports/summary', [ReportController::class, 'summary']);

});
// for Customer

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1');
Route::post('/payments/bakong/checkout/{orderId}', [PaymentController::class, 'checkout'])
    ->whereNumber('orderId')->middleware('throttle:20,1');
Route::post('/payments/bakong/verify', [PaymentController::class, 'verifyTransaction'])
    ->middleware('throttle:30,1');







