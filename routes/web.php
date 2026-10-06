<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\TrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/products/{product:slug}/model', [StorefrontController::class, 'model'])->name('products.model');
Route::get('/checkout/{variant}', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout/{variant}', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/orders/confirmation/{code}', [TrackingController::class, 'confirmation'])->name('orders.confirmation');
Route::get('/track', function (Request $request) {
    $code = trim((string) $request->query('code', ''));

    return $code !== '' ? redirect()->route('tracking.show', $code) : view('tracking.lookup');
})->name('tracking.lookup');
Route::get('/track/{code}', [TrackingController::class, 'show'])->middleware('throttle:60,1')->name('tracking.show');
Route::get('/history', [TrackingController::class, 'index'])->name('history.index');
Route::post('/history/send-otp', [TrackingController::class, 'sendOtp'])->middleware('throttle:3,5')->name('history.send-otp');
Route::post('/history/verify-otp', [TrackingController::class, 'verifyOtp'])->middleware('throttle:10,5')->name('history.verify-otp');
Route::delete('/history/session', [TrackingController::class, 'forgetHistory'])->name('history.forget');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::post('/colors', [ColorController::class, 'store'])->name('colors.store');
        Route::delete('/colors/{color}', [ColorController::class, 'destroy'])->name('colors.destroy');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    });
});
