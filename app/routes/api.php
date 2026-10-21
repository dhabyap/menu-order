<?php

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/store-config', [StoreConfigController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [CheckoutController::class, 'store'])->middleware('throttle:10,1');
