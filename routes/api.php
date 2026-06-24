<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BarbershopProfileController;
use App\Http\Controllers\Api\V1\ServicePlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::get('/auth/google/config', [AuthController::class, 'googleConfig']);
    Route::post('/auth/google', [AuthController::class, 'googleLogin']);
    Route::post('/auth/google/register', [AuthController::class, 'googleRegister']);

    Route::get('/barbearias/{username}', [BarbershopProfileController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/barbearias/{username}/service-plans/checkout', [ServicePlanController::class, 'checkout']);
        Route::post('/barbearias/{username}/membership', [ServicePlanController::class, 'membership']);
    });
});
