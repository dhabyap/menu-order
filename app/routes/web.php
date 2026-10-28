<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StoreSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('catalog');
});

Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/settings', [StoreSettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/table', [OrderController::class, 'table'])->name('admin.orders.table');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('admin.orders.show');
    Route::patch('/orders/{id}/status', [OrderController::class, 'update'])->whereNumber('id')->name('admin.orders.update');

    Route::post('/products', [ProductController::class, 'store'])->name('admin.products.create');
    Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id')->name('admin.products.update');
    Route::patch('/products/{id}', [ProductController::class, 'toggle'])->whereNumber('id')->name('admin.products.toggle');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->name('admin.products.delete');
    Route::patch('/products/reorder', [ProductController::class, 'reorder'])->name('admin.products.reorder');
});
