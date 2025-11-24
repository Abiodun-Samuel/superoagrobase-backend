<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
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

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('featured', [ProductController::class, 'getFeaturedProducts']);
        Route::get('trending', [ProductController::class, 'getTrendingProducts']);
        Route::get('{product}', [ProductController::class, 'show']);
    });
});

Route::middleware('auth:sanctum')->group(function () {});
