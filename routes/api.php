<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VendorRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
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
    });
});

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

Route::prefix('blogs')->group(function () {
    Route::get('/published', [BlogController::class, 'published']);
    Route::get('/featured', [BlogController::class, 'featured']);
    Route::get('/{blog}', [BlogController::class, 'show']);
});

Route::post('/transactions/webhook', [TransactionController::class, 'webhook']);

Route::post('/vendor-requests', [VendorRequestController::class, 'store']);
Route::get('/vendor-requests', [VendorRequestController::class, 'getByEmail']);
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{review}', [ReviewController::class, 'update']);
        Route::delete('/{review}', [ReviewController::class, 'destroy']);
    });

    // ==================== USER ORDER ROUTES ====================
    Route::prefix('orders')->name('my-orders.')->group(function () {
        Route::post('/complete', [OrderController::class, 'completeOrder'])
            ->name('orders.complete');
        Route::get('/', [OrderController::class, 'myOrders'])
            ->name('index');
        Route::get('/{order}', [OrderController::class, 'myOrder'])
            ->name('show');
        Route::patch('/{order}/status', [OrderController::class, 'updateMyOrderStatus'])
            ->name('update-status');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserProfileController::class, 'show'])->name('show');
        Route::put('/basic-info', [UserProfileController::class, 'updateBasicInfo'])->name('update.basic');
        Route::put('/personal-details', [UserProfileController::class, 'updatePersonalDetails'])->name('update.personal');
        Route::put('/address', [UserProfileController::class, 'updateAddress'])->name('update.address');
        Route::put('/preferences', [UserProfileController::class, 'updatePreferences'])->name('update.preferences');
        Route::post('/avatar', [UserProfileController::class, 'updateAvatar'])->name('update.avatar');
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
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/dashboard/clear-cache', [AdminController::class, 'clearCache'])
            ->name('admin.dashboard.clear-cache');
        // Vendor Management (same as users, filter by role)
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
            Route::post('/{user}/assign-role', [UserController::class, 'assignRole']);
        });
        // Product Management
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'adminIndex']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/{product}', [ProductController::class, 'adminShow']);
            Route::put('/{product}', [ProductController::class, 'update']);
            Route::delete('/{product}', [ProductController::class, 'destroy']);
            Route::post('/bulk/feature', [ProductController::class, 'bulkUpdateFeatured'])->name('products.bulk-feature');
        });
        // Category Management
        Route::apiResource('categories', CategoryController::class);

        // Subcategory Management
        Route::apiResource('subcategories', SubcategoryController::class);

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
            Route::get('/{order}', [OrderController::class, 'show'])
                ->name('show');
            Route::put('/{order}', [OrderController::class, 'update'])
                ->name('update');
            Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])
                ->name('update-status');
        });
        Route::prefix('blogs')->name('blogs.')->group(function () {
            Route::get('/', [BlogController::class, 'index']);
            Route::post('/', [BlogController::class, 'store']);
            Route::put('/{blog}', [BlogController::class, 'update']);
            Route::delete('/{blog}', [BlogController::class, 'destroy']);
        });
    });
    //super admin
    Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])
            ->name('orders.destroy');
    });
});
