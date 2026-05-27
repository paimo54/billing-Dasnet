<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
*/

// Payment callbacks (no auth required - called by payment gateway)
Route::post('/payment/duitku/callback', [PaymentController::class, 'duitkuCallback'])->name('payment.duitku.callback');
Route::post('/payment/qris/callback', [PaymentController::class, 'qrisCallback'])->name('payment.qris.callback');

// Payment API (requires auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment/create', [PaymentController::class, 'create'])->name('api.payment.create');
    Route::get('/payment/{payment}/status', [PaymentController::class, 'checkStatus'])->name('api.payment.status');
    Route::get('/payment/invoice/{invoice}/history', [PaymentController::class, 'history'])->name('api.payment.history');
});
