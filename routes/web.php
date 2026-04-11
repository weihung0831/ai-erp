<?php

use App\Http\Controllers\Web\ComponentShowcaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/components');
});

Route::get('/components', ComponentShowcaseController::class)->name('components.showcase');
