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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::group(['middleware' => 'advertiser'],function(){
        Route::get('/get-advertiser/{id?}', [UserController::class, 'getAdvertiser']);
        Route::post('/update-advertiser', [UserController::class, 'updateAdvertiser']);
        Route::post('/update-images', [UserController::class, 'updateImages']);
        Route::post('/update-videos', [UserController::class, 'updateVideos']);
        Route::post('/update-my-account', [UserController::class, 'updateMyAccount']);
        Route::post('/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('/create-advertisement', [AdvertisementController::class, 'createAdvertisement']);
        Route::get('/get-advertisements/{id?}', [AdvertisementController::class, 'getAdvertisement']);
        Route::delete('/delete-advertisement/{id}', [AdvertisementController::class, 'deleteAdvertisement']);
        Route::post('/update-advertisement/{id}', [AdvertisementController::class, 'updateAdvertisement']);
        Route::get('/renew-advertisement/{id}', [AdvertisementController::class, 'renewAdvertisement']);
        Route::post('/create-availability', [AdvertisementController::class, 'createAvailability']);
        Route::get('/get-availabilities', [AdvertisementController::class, 'getAvailabilities']);
        Route::post('/update-availability/{id}', [AdvertisementController::class, 'updateAvailability']);
        Route::delete('/delete-availability/{id}', [AdvertisementController::class, 'deleteAvailability']);
        Route::post('/update-password', [UserController::class, 'updatePassword']);
        Route::delete('/delete-advertiser', [UserController::class, 'deleteAdvertiser']);
    });
    
    Route::middleware('role:Super_Admin,Manager,Support')->group(function () {
        // Routes accessible by Super_Admin, Manager, and Support
        Route::get('/get-user/{id?}', [AdminController::class, 'getUser']);
    });

    Route::middleware('role:Super_Admin')->group(function () {
        Route::get('/get-advertiser-by-admin/{id?}', [UserController::class, 'getAdvertiser']);
        Route::get('/get-advertisement-by-id/{id?}', [AdvertisementController::class, 'getAdvertisementById']);
        Route::get('/get-advertisements-by-admin/{id?}', [AdvertisementController::class, 'getAdvertisement']);
        Route::delete('/delete-advertisement-by-admin/{id}', [AdvertisementController::class, 'deleteAdvertisement']);
        Route::delete('/delete-advertiser-permanently/{id}', [UserController::class, 'deleteAdvertiserPermanently']);

        Route::post('/update-profile-photo-admin', [AdminController::class, 'updateProfilePhotoAdmin']);
        Route::post('/update-account-admin', [AdminController::class, 'updateAccountAdmin']);
        Route::post('/update-password-admin', [AdminController::class, 'updatePasswordAdmin']);
        Route::post('/create-user', [AdminController::class, 'createUser']);
        Route::get('/get-all-advertisers', [AdminController::class, 'getAllAdvertisers']);
        Route::get('/get-dashboard-totals', [AdminController::class, 'getDashboardTotals']);
        Route::get('/get-managers-supports', [AdminController::class, 'getManagersAndSupports']);
        Route::get('/get-all-advertisements', [AdvertisementController::class, 'getAllAdvertisements']);
        Route::get('/get-latest-advertisements', [AdvertisementController::class, 'getLatestAdvertisements']);
        Route::get('/pause-advertisement/{id}', [AdvertisementController::class, 'pauseAdvertisement']);
        Route::delete('/trash-advertisement/{id}', [AdvertisementController::class, 'trashAdvertisement']);
        Route::post('/restore-advertisement/{id}', [AdvertisementController::class, 'restoreAdvertisement']);
        Route::post('/activate-paused-advertisement/{id}', [AdvertisementController::class, 'activatePausedAdvertisement']);
        Route::get('/get-paused-advertisements', [AdvertisementController::class, 'getPausedAdvertisements']);
        Route::get('/get-trashed-advertisements', [AdvertisementController::class, 'getTrashedAdvertisements']);
        Route::delete('/delete-image-by-id/{id}', [AdvertisementController::class, 'deleteImageById']);
        Route::delete('/delete-video-by-id/{id}', [AdvertisementController::class, 'deleteVideoById']);
    });
});