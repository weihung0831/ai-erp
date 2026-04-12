<?php

use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\ChatPageController;
use App\Http\Controllers\Web\DashboardPageController;
use Illuminate\Support\Facades\Route;

// Auth pages
Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.forgot');
Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');

// App pages
Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/chat', [ChatPageController::class, 'index'])->name('chat');
Route::get('/dashboard', [DashboardPageController::class, 'index'])->name('dashboard');
