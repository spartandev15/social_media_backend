<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdvertisementController;

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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/send-otp', [AuthController::class, 'sendOTP']);
Route::post('/forgot-password', [AuthController::class, 'forgotpassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/is-token-valid', [AuthController::class, 'isTokenValid']);

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['middleware' => 'advertiser'],function(){
        Route::get('/get-advertiser/{id?}', [UserController::class, 'getAdvertiser']);
        Route::post('/update-advertiser', [UserController::class, 'updateAdvertiser']);
        Route::post('/update-images', [UserController::class, 'updateImages']);
        Route::post('/update-videos', [UserController::class, 'updateVideos']);
        Route::post('/update-my-account', [UserController::class, 'updateMyAccount']);
        Route::post('/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('/create-advertisement', [AdvertisementController::class, 'createAdvertisement']);
        Route::get('/get-advertisements', [AdvertisementController::class, 'getAdvertisement']);
        Route::delete('/delete-advertisement/{id}', [AdvertisementController::class, 'deleteAdvertisement']);
        Route::post('/update-advertisement/{id}', [AdvertisementController::class, 'updateAdvertisement']);
        Route::get('/renew-advertisement/{id}', [AdvertisementController::class, 'renewAdvertisement']);
        Route::post('/create-availability', [AdvertisementController::class, 'createAvailability']);
        Route::get('/get-availabilities', [AdvertisementController::class, 'getAvailabilities']);
        Route::post('/update-availability/{id}', [AdvertisementController::class, 'updateAvailability']);
    });
    
    Route::group(['middleware' => 'admin'],function(){
        Route::get('/get-super-admin/{id?}', [AdminController::class, 'getSuperAdmin']);
    });
});