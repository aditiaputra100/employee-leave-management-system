<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware(CheckToken::using('admin'));

    // Leave Request routes
    Route::get('/leave-requests', [App\Http\Controllers\Api\LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [App\Http\Controllers\Api\LeaveRequestController::class, 'store']);
    Route::get('/leave-requests/{id}', [App\Http\Controllers\Api\LeaveRequestController::class, 'show']);

    // Admin only: update leave request status
    Route::patch('/leave-requests/{id}/status', [App\Http\Controllers\Api\LeaveRequestController::class, 'updateStatus'])
        ->middleware(CheckToken::using('admin'));
});


