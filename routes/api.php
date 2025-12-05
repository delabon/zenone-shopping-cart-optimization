<?php

use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CartOptimizationController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/cart/items', [CartItemController::class, 'store'] )
    ->middleware(['auth:sanctum', 'throttle:40,1']);

Route::post('/v1/cart/optimize', [CartOptimizationController::class, 'optimize'] )
    ->middleware(['auth:sanctum', 'throttle:10,1']);
