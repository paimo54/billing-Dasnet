<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\CoverageAreaController;
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

/*
|--------------------------------------------------------------------------
| Network Management Routes (FreeRADIUS + Mikrotik)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('network')->name('api.network.')->group(function () {
    // Suspend/Unsuspend
    Route::post('/customer/{customer}/suspend', [NetworkController::class, 'suspend'])->name('suspend');
    Route::post('/customer/{customer}/unsuspend', [NetworkController::class, 'unsuspend'])->name('unsuspend');

    // Status
    Route::get('/customer/{customer}/status', [NetworkController::class, 'status'])->name('status');

    // Batch operations
    Route::post('/batch/suspend', [NetworkController::class, 'batchSuspend'])->name('batch.suspend');
});

/*
|--------------------------------------------------------------------------
| Router Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('routers')->name('api.routers.')->group(function () {
    Route::get('/health', [RouterController::class, 'checkHealth'])->name('health');
    Route::get('/{router}/health', [RouterController::class, 'checkRouterHealth'])->name('router.health');
    Route::get('/statistics', [RouterController::class, 'statistics'])->name('statistics');
});

/*
|--------------------------------------------------------------------------
| Coverage Area Routes (Public + Auth)
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/coverage-areas/geojson', [CoverageAreaController::class, 'geojson'])->name('api.coverage-areas.geojson');
Route::get('/coverage-areas/by-region', [CoverageAreaController::class, 'byRegion'])->name('api.coverage-areas.by-region');

