<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

// ─── Business routes (auth required) ─────────────────────────────
    Route::get('/business', [BusinessController::class, 'show']);
    Route::post('/business', [BusinessController::class, 'store']);
    Route::patch('/business', [BusinessController::class, 'update']);
});

// ─── Public routes (no auth required) ─────────────────────────────
Route::get('/businesses', [BusinessController::class, 'index']);
Route::get('/businesses/{slug}', [BusinessController::class, 'profile']);

