<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\ChatPageController;
use App\Http\Controllers\Web\ComponentShowcaseController;
use Illuminate\Support\Facades\Route;

// Showcase
Route::get('/components', ComponentShowcaseController::class)->name('components.showcase');

// Auth pages
Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.forgot');
Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');

// Chat (main)
Route::get('/', fn () => redirect()->route('chat'));
Route::get('/chat', [ChatPageController::class, 'index'])->name('chat');

// Admin
Route::prefix('admin')->group(function () {
    Route::get('/query-logs', [AdminController::class, 'queryLogs'])->name('admin.query-logs');
    Route::get('/quick-actions', [AdminController::class, 'quickActions'])->name('admin.quick-actions');
    Route::get('/schema-fields', [AdminController::class, 'schemaFields'])->name('admin.schema-fields');
});
