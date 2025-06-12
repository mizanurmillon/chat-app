<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::controller(UserController::class)->group(function () {
    Route::get('dashboard', 'index')->name('dashboard');
    Route::get('user/chat/{id}', 'userChat')->name('chat');
})->middleware(['auth', 'verified']);

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
