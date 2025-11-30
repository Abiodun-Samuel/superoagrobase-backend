<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('guest')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store']);

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('trending', [ProductController::class, 'getTrendingProducts']);
        Route::get('featured', [ProductController::class, 'getFeaturedProducts']);
        Route::get('{product}', [ProductController::class, 'show']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);
        Route::put('/items/{cartItemId}', [CartController::class, 'update']);
        Route::delete('/items/{cartItemId}', [CartController::class, 'destroy']);
        Route::post('/clear', [CartController::class, 'clear']);
        // Route::post('/validate', [CartController::class, 'validate']); // New endpoint
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
    });
});

Route::middleware('auth:sanctum')->group(function () {});
