<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollectorController;
use App\Http\Controllers\Api\ConsumerController;
use App\Http\Controllers\Api\DistributorController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\PublicTraceController;
use App\Http\Controllers\Api\UmkmController;

Route::post('/register',[AuthController::class,'register']);

Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){

    Route::get('/me',[AuthController::class,'me']);

    Route::post('/logout',[AuthController::class,'logout']);

    Route::prefix('farmer')->group(function (): void {
        Route::get('/profile', [FarmerController::class, 'profile']);
        Route::put('/profile', [FarmerController::class, 'updateProfile']);
        Route::get('/farms', [FarmerController::class, 'farms']);
        Route::post('/farms', [FarmerController::class, 'storeFarm']);
        Route::get('/batches', [FarmerController::class, 'batches']);
        Route::post('/batches', [FarmerController::class, 'storeBatch']);
        Route::get('/batches/{code}', [FarmerController::class, 'showBatch']);
        Route::patch('/batches/{code}', [FarmerController::class, 'updateBatch']);
    });

    Route::prefix('collector')->group(function (): void {
        Route::get('/profile', [CollectorController::class, 'profile']);
        Route::put('/profile', [CollectorController::class, 'updateProfile']);
        Route::get('/stock', [CollectorController::class, 'stock']);
        Route::get('/shipment-batches', [CollectorController::class, 'shipmentBatches']);
        Route::post('/shipment-batches', [CollectorController::class, 'storeShipmentBatches']);
        Route::patch('/shipment-batches/{code}/send', [CollectorController::class, 'sendShipment']);
        Route::patch('/shipment-batches/{code}/complete', [CollectorController::class, 'completeShipment']);
        Route::post('/batches/{code}/verify', [CollectorController::class, 'verifyBatch']);
        Route::post('/batches/{code}/reject', [CollectorController::class, 'rejectBatch']);
    });

    Route::prefix('distributor')->group(function (): void {
        Route::get('/profile', [DistributorController::class, 'profile']);
        Route::put('/profile', [DistributorController::class, 'updateProfile']);
        Route::get('/shipments', [DistributorController::class, 'shipments']);
        Route::get('/shipments/{code}', [DistributorController::class, 'showShipment']);
        Route::post('/shipments/{code}/receipt', [DistributorController::class, 'storeReceipt']);
    });

    Route::prefix('umkm')->group(function (): void {
        Route::get('/profile', [UmkmController::class, 'profile']);
        Route::put('/profile', [UmkmController::class, 'updateProfile']);
        Route::get('/products', [UmkmController::class, 'products']);
        Route::post('/products', [UmkmController::class, 'storeProduct']);
        Route::get('/orders', [UmkmController::class, 'orders']);
        Route::post('/orders', [UmkmController::class, 'storeOrder']);
    });

    Route::prefix('consumer')->group(function (): void {
        Route::get('/profile', [ConsumerController::class, 'profile']);
        Route::put('/profile', [ConsumerController::class, 'updateProfile']);
        Route::get('/products', [ConsumerController::class, 'products']);
        Route::get('/products/{code}', [ConsumerController::class, 'showProduct']);
        Route::get('/transactions', [ConsumerController::class, 'transactions']);
        Route::post('/transactions', [ConsumerController::class, 'storeTransaction']);
    });

});

Route::get('/trace/{batchCode}', [PublicTraceController::class, 'show']);
