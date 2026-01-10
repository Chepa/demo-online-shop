<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::get('/user', [AuthController::class, 'user']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/cart', [CartController::class, 'store']);

    Route::prefix('cart')->group(function () {
        Route::put('/{product}', [CartController::class, 'update']);
        Route::delete('/{product}', [CartController::class, 'destroy']);
    });

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);

    Route::middleware('ensure_user_is_admin')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('/orders', [OrderController::class, 'adminIndex']);
            Route::patch('/orders/{order}', [OrderController::class, 'updateStatus']);
            Route::apiResource('/categories', CategoryController::class)->except(['show']);
            Route::apiResource('/products', ProductController::class);
        });
    });
});
