<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AvailabilityApiController;
use App\Http\Controllers\Api\LoginApiController;

// 🔓 Public API routes
Route::post('/login', [LoginApiController::class, 'login']);
Route::post('/register', [LoginApiController::class, 'register']);

// 🔐 Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginApiController::class, 'logout']);

    Route::post('/availability', [AvailabilityApiController::class, 'store']);
    Route::get('/availability/{doctor}', [AvailabilityApiController::class, 'show']);
});