<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentModerationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TitleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Welcome / Status check (JSON API) & Home
Route::get('/status', function (Request $request) {
    if ($request->expectsJson()) {
        return response()->json([
            'system' => 'Entertainment Community Platform API',
            'status' => 'online',
            'time'   => now()->toDateTimeString(),
        ]);
    }
    return redirect()->route('home');
})->name('status');

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/feed', [PageController::class, 'feed'])->name('feed');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth')->name('me');

// ========================================================
// ADMIN BACKEND ROUTES (Protected by 'auth' & 'admin' middleware)
// ========================================================
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {

    // MODULE 01: Dashboard & Live Statistics
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // MODULE 02: User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{id}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

    // MODULE 03: Title Submission & Management
    Route::get('/titles', [TitleController::class, 'index'])->name('titles.index');
    Route::get('/titles/pending', [TitleController::class, 'pendingQueue'])->name('titles.pending');
    Route::post('/titles/{id}/review', [TitleController::class, 'reviewSubmission'])->name('titles.review');
    Route::post('/titles', [TitleController::class, 'store'])->name('titles.store');
    Route::put('/titles/{id}', [TitleController::class, 'update'])->name('titles.update');
    Route::post('/titles/{id}/hide', [TitleController::class, 'hide'])->name('titles.hide');
    Route::post('/titles/{id}/restore', [TitleController::class, 'restore'])->name('titles.restore');

    // MODULE 04: Genre Management
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    Route::put('/genres/{id}', [GenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{id}', [GenreController::class, 'destroy'])->name('genres.destroy');
    Route::post('/titles/{titleId}/genres', [GenreController::class, 'assignToTitle'])->name('genres.assign');
    Route::delete('/titles/{titleId}/genres/{genreId}', [GenreController::class, 'removeFromTitle'])->name('genres.remove');

    // MODULE 05: Discussion Category Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // MODULE 06: Report Management
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{id}/reject', [ReportController::class, 'reject'])->name('reports.reject');
    Route::post('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');

    // MODULE 07: Content Moderation & Soft Deletion
    Route::get('/moderation', [ContentModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/hide', [ContentModerationController::class, 'hideContent'])->name('moderation.hide');
    Route::post('/moderation/restore', [ContentModerationController::class, 'restoreContent'])->name('moderation.restore');

    // MODULE 08: Admin Activity Logs (Read-only audit trail)
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/{id}', [ActivityLogController::class, 'show'])->name('logs.show');
});