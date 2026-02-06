<?php

use App\Http\Controllers\VendorRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VendorProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-token', [AuthController::class, 'verifyToken']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        // Route::get('/me', [AuthController::class, 'me']);
        // //Profile
        // Route::post('/change-password', [AuthController::class, 'changePassword']);
        // Route::get('/profile', [AuthController::class, 'getProfile']);
        // Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

// Route::middleware('guest')->group(function () {
Route::post('contact/submit', [ContactController::class, 'submit']);
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
});

Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);
});

Route::post('/transactions/webhook', [TransactionController::class, 'webhook']);

Route::post('/vendor-requests', [VendorRequestController::class, 'store']);
Route::get('/vendor-requests', [VendorRequestController::class, 'getByEmail']);
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/my-orders', [OrderController::class, 'myOrders'])
            ->name('index');
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/complete', [OrderController::class, 'completeOrder']);
        Route::patch('/{order}/status', [OrderController::class, 'updateMyOrderStatus'])
            ->name('update-status');
        Route::get('/{order}', [OrderController::class, 'show']);
    });
    //Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/verify', [TransactionController::class, 'verify']);
    });

    // vendor
    Route::middleware(['role:vendor'])->prefix('vendor/products')->name('vendor.products.')->group(function () {
        Route::get('/', [VendorProductController::class, 'index'])->name('index');
        Route::post('/', [VendorProductController::class, 'store'])->name('store');
        Route::put('/', [VendorProductController::class, 'update'])->name('update');
        Route::delete('/', [VendorProductController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [VendorProductController::class, 'show'])->name('show'); // ✅ Last
    });

    // admin
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        // vendor requests
        Route::prefix('vendor-requests')->group(function () {
            Route::get('/', [VendorRequestController::class, 'index']);
            Route::get('/{vendorRequest}', [VendorRequestController::class, 'show']);
            Route::post('/{vendorRequest}/approve', [VendorRequestController::class, 'approve']);
            Route::post('/{vendorRequest}/reject', [VendorRequestController::class, 'reject']);
        });
        // orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])
                ->name('index');
            Route::post('/bulk-update-status', [OrderController::class, 'bulkUpdateStatus'])
                ->name('bulk-update-status');
            Route::get('/{order}', [OrderController::class, 'show'])
                ->name('show');
            Route::put('/{order}', [OrderController::class, 'update'])
                ->name('update');
            Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])
                ->name('update-status');
            Route::delete('/{order}', [OrderController::class, 'destroy'])
                ->middleware('role:super_admin')
                ->name('destroy');
        });
    });
});
