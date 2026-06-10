<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Domain\Auth\Controllers\RegisterController;
use App\Domain\Auth\Controllers\LoginController;
use App\Domain\Auth\Controllers\LogoutController;
use App\Domain\Auth\Controllers\MeController;

// Service Controllers
use App\Domain\Services\Controllers\CategoryController;
use App\Domain\Services\Controllers\ServiceController;
use App\Domain\Services\Controllers\CreatorServiceController;

// Order Controllers
use App\Domain\Orders\Controllers\OrderController;
use App\Domain\Orders\Controllers\OrderStatusController;
use App\Domain\Orders\Controllers\OrderMessageController;
use App\Domain\Orders\Controllers\OrderDeliveryController;

// Payment Controllers
use App\Domain\Payments\Controllers\PaymentController;
use App\Domain\Payments\Controllers\WebhookController;
use App\Domain\Payments\Controllers\PayoutController;
use App\Domain\Payments\Controllers\StripeConnectController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API Info
Route::get('/', function () {
    return response()->json([
        'api' => 'NexCreate API',
        'version' => 'v1',
        'status' => 'active',
        'documentation' => '/api/v1/docs',
    ]);
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
    ]);
});

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public routes (throttled against brute-force / mass signup)
        Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [LogoutController::class, 'store']);
            Route::get('me', [MeController::class, 'show']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Public Marketplace Routes
    |--------------------------------------------------------------------------
    */
    
    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    // Services (Public)
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{slug}', [ServiceController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Creator Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->prefix('creator')->group(function () {
        // My Services
        Route::get('my-services', [CreatorServiceController::class, 'index']);
        Route::get('services/{id}', [CreatorServiceController::class, 'show']);
        Route::post('services', [CreatorServiceController::class, 'store']);
        Route::put('services/{id}', [CreatorServiceController::class, 'update']);
        Route::patch('services/{id}/status', [CreatorServiceController::class, 'updateStatus']);
        Route::delete('services/{id}', [CreatorServiceController::class, 'destroy']);

        // Creator Balance & Payouts
        Route::get('balance', [PayoutController::class, 'balance']);
        Route::get('payouts', [PayoutController::class, 'index']);
        Route::post('payouts/request', [PayoutController::class, 'store']);
        Route::post('payouts/{id}/cancel', [PayoutController::class, 'cancel']);

        // Stripe Connect
        Route::get('stripe/status', [StripeConnectController::class, 'status']);
        Route::post('stripe/onboard', [StripeConnectController::class, 'onboard']);
        Route::post('stripe/complete', [StripeConnectController::class, 'complete']);
    });

    /*
    |--------------------------------------------------------------------------
    | Order Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // Orders CRUD
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);

        // Order Status
        Route::patch('orders/{id}/status', [OrderStatusController::class, 'update']);

        // Order Messages (Chat)
        Route::get('orders/{id}/messages', [OrderMessageController::class, 'index']);
        Route::post('orders/{id}/messages', [OrderMessageController::class, 'store']);

        // Order Deliveries
        Route::get('orders/{id}/deliveries', [OrderDeliveryController::class, 'index']);
        Route::post('orders/{id}/deliveries', [OrderDeliveryController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Payment Routes
    |--------------------------------------------------------------------------
    */
    
    // Stripe Webhook (No Auth - Stripe calls this; signature-verified + throttled)
    Route::post('payments/webhook', [WebhookController::class, 'handle'])->middleware('throttle:120,1');

    // Protected Payment Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Checkout
        Route::post('payments/checkout', [PaymentController::class, 'checkout']);
        
        // My Payments (as buyer)
        Route::get('me/payments', [PaymentController::class, 'index']);
        Route::get('me/payments/{id}', [PaymentController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | User Profile Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->prefix('me')->group(function () {
        // Profile management will be added here
    });

});
