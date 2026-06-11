<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public catalog routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/categories', [ProductController::class, 'categories']);

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Webhook routes
Route::post('/webhooks/payment', [WebhookController::class, 'handlePayment']);

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Middleware\EnsureUserIsAdmin;

// Protected auth and order routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
});

// Protected admin routes
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    // Categories Admin CRUD
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    // Products Admin CRUD
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);
});
