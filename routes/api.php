<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\LeaderController;
use App\Http\Controllers\Api\V1\MemberController;

Route::prefix('v1')->group(function () {

    // 1. Authentication & Profile APIs
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/profile', [AuthController::class, 'profile']);
        });
    });

    // 2. Public Tracking & Webhook APIs
    Route::prefix('track')->group(function () {
        Route::get('/click/{unique_hash}', [TrackingController::class, 'click']);
        Route::post('/postback', [TrackingController::class, 'postback']);
    });

    // 3. Admin (Master) APIs
    Route::prefix('admin')->middleware(['auth:sanctum', RoleMiddleware::class.':admin'])->group(function () {
        Route::get('/dashboard/stats', [AdminController::class, 'stats']);
        Route::apiResource('campaigns', AdminController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/groups', [AdminController::class, 'groups']);
        Route::post('/groups', [AdminController::class, 'storeGroup']);
        Route::get('/payouts', [AdminController::class, 'payouts']);
        Route::post('/payouts/{id}/approve', [AdminController::class, 'approvePayout']);
    });

    // 4. Group Leader APIs
    Route::prefix('leader')->middleware(['auth:sanctum', RoleMiddleware::class.':leader'])->group(function () {
        Route::get('/dashboard/stats', [LeaderController::class, 'stats']);
        Route::get('/team', [LeaderController::class, 'team']);
        Route::post('/team/invite', [LeaderController::class, 'inviteMember']);
        Route::delete('/team/members/{id}', [LeaderController::class, 'removeMember']);
        Route::get('/campaigns', [LeaderController::class, 'campaigns']);
        Route::post('/campaigns/{id}/distribute', [LeaderController::class, 'distributeCampaign']);
        Route::get('/earnings', [LeaderController::class, 'earnings']);
    });

    // 5. Member APIs
    Route::prefix('member')->middleware(['auth:sanctum', RoleMiddleware::class.':member'])->group(function () {
        Route::get('/dashboard/stats', [MemberController::class, 'stats']);
        Route::get('/campaigns', [MemberController::class, 'campaigns']);
        Route::get('/links', [MemberController::class, 'links']);
        Route::post('/links/customize', [MemberController::class, 'customizeLink']);
        Route::get('/stats', [MemberController::class, 'granularStats']);
        Route::post('/wallet/withdraw', [MemberController::class, 'withdraw']);
    });
});
