<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::prefix('translations')->group(function () {
        Route::get('/{id}', [TranslationController::class, 'show']);
        Route::post('/', [TranslationController::class, 'store']);
        Route::put('/{id}', [TranslationController::class, 'update']);
        Route::delete('/{id}', [TranslationController::class, 'destroy']);
        Route::get('/search', [TranslationController::class, 'search']);
        
        // Export endpoint with higher rate limit
        Route::middleware('throttle:120,1')->get('/export', [TranslationController::class, 'export']);
    });
});
