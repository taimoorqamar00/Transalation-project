<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('translations')->group(function () {
        Route::post('/', [TranslationController::class, 'store']);
        Route::get('/search', [TranslationController::class, 'search']);
        Route::get('/export', [TranslationController::class, 'export']);
    });
});

