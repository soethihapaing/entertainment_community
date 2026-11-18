<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Point of View — Web Routes (frontend only)
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// TODO (Backend): Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
// TODO (Backend): Route::post('/register', [AuthController::class, 'register'])->name('register.store');
// TODO (Backend): Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// TODO (Backend): add ->middleware('auth') once login works.
Route::get('/feed', [PageController::class, 'feed'])->name('feed');
