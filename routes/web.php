<?php

use Illuminate\Support\Facades\Route;
use PDGIOnline\Auth\Http\Controllers\AuthController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/auth/pdgi', [AuthController::class, 'redirect'])->name('pdgi.auth');
    Route::get('/auth/pdgi/callback', [AuthController::class, 'callback'])->name('pdgi.callback');
});
