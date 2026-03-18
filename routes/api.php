<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BusinessDocumentController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\BusinessSnapshotsController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::patch('/user', [AuthController::class, 'update']);
    Route::delete('/user', [AuthController::class, 'delete']);
    Route::get('/score', [ScoreController::class, 'show']);

// ─── Business routes (auth required) ─────────────────────────────
    Route::get('/business', [BusinessController::class, 'show']);
    Route::post('/business', [BusinessController::class, 'store']);
    Route::patch('/business', [BusinessController::class, 'update']);

    // Sales routes (auth required)
    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/{sale}', [SaleController::class, 'show']);
    Route::patch('/sales/{sale}', [SaleController::class, 'update']);
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

    // Expenses routes (auth required)
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

    // Business documents routes (auth required)
    Route::get('/business-documents', [BusinessDocumentController::class, 'index']);
    Route::post('/business-documents', [BusinessDocumentController::class, 'store']);
    Route::get('/business-documents/{businessDocument}', [BusinessDocumentController::class, 'show']);
    Route::patch('/business-documents/{businessDocument}', [BusinessDocumentController::class, 'update']);
    Route::delete('/business-documents/{businessDocument}', [BusinessDocumentController::class, 'destroy']);

    // Business snapshots routes (auth required)
    Route::get('/business-snapshots', [BusinessSnapshotsController::class, 'index']);
    Route::get('/business-snapshots/{month}', [BusinessSnapshotsController::class, 'show'])
        ->where('month', '\d{4}-\d{2}');
    Route::post('/business-snapshots/generate', [BusinessSnapshotsController::class, 'generate']);
});

// ─── Public routes (no auth required) ─────────────────────────────
Route::get('/businesses', [BusinessController::class, 'index']);
Route::get('/businesses/{slug}', [BusinessController::class, 'profile']);

