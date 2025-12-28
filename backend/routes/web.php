<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'NexCreate',
        'version' => '1.0.0',
        'status' => 'running',
        'message' => 'Welcome to NexCreate - UGC + AI + Marketplace Platform',
        'documentation' => '/api/docs',
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
    ]);
});
