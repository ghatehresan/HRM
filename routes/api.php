<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\ApiTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/tokens', [ApiTokenController::class, 'store'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'is-active'])->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::get('/tokens', [ApiTokenController::class, 'index']);
        Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy'])->whereNumber('id');
    });
});
