<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerAuthController;

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

//Auth Related
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::get('login', [AuthController::class, 'login'])->name('login'); //Added for escape error on logout without token
Route::middleware('auth:api')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
});

//Booking Related
Route::post('bookings', [BookingController::class, 'store']);
Route::middleware('auth:api')->group(function () {
    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/{id}', [BookingController::class, 'show']);
    Route::post('bookings/{id}/update', [BookingController::class, 'update']);
    Route::post('bookings/{id}/destroy', [BookingController::class, 'destroy']);
});

//Product Related
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::middleware('seller')->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::post('products/{id}/update', [ProductController::class, 'update']);
    Route::post('products/{id}/destroy', [ProductController::class, 'destroy']);
});

//Seller Related
Route::prefix('seller')->group(function () {
    Route::post('login', [SellerAuthController::class, 'login'])->name('login_seller');
    Route::get('login', [SellerAuthController::class, 'login'])->name('login_seller'); //Added for escape error on logout without token
    Route::post('register', [SellerAuthController::class, 'register']);
    Route::middleware('seller')->group(function () {
        Route::get('user', [SellerAuthController::class, 'user']);
        Route::post('logout', [SellerAuthController::class, 'logout']);
    });
});