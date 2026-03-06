<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/ping', function () {
    return response()->json(['message' => 'Kreditsu API(laravel server) is running ']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

// {
//   "name": "John Doe",
//   "email": "john@example.com",
//   "password": "password123",
//   "password_confirmation": "password123"
// }