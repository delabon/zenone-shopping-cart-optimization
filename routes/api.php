<?php

use App\Http\Controllers\Api\V1\CartItemController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/cart/items', [CartItemController::class, 'store'] )
    ->middleware('auth:sanctum');
