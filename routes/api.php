<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatHistoryController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.password.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.password.reset');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/token/refresh', [AuthController::class, 'refresh'])->name('api.token.refresh');
    Route::get('/user', [AuthController::class, 'me'])->name('api.user.me');

    Route::middleware('tenant')->group(function (): void {
        Route::post('/chat', [ChatController::class, 'handle'])->name('api.chat');
        Route::get('/chat/history', [ChatHistoryController::class, 'index'])->name('api.chat.history');
        Route::get('/chat/history/{conversationUuid}', [ChatHistoryController::class, 'show'])->name('api.chat.history.show');
    });
});
