<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Public routes
Route::prefix('auth')->group(function () {
    // Email & Password Authentication
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // Rate limiting: 5 attempts per minute
        ->name('api.login');

    // Password Reset
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])
        ->middleware('throttle:3,1')
        ->name('api.password.forgot');
    Route::post('/reset-password', [PasswordController::class, 'resetPassword'])
        ->name('api.password.reset');

    // Google OAuth
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->name('api.google.redirect');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('api.google.callback');
    Route::get('/google/debug', [GoogleAuthController::class, 'debug'])
        ->name('api.google.debug');
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // User information
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Users list (for testing/admin)
    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('api.users.show');
    Route::get('/user/me', [UserController::class, 'me'])->name('api.users.me');
    Route::get('/user/income/{date}', [UserController::class, 'income'])->name('api.users.income');
    Route::put('/user/income/{date}', [UserController::class, 'income'])->name('api.users.income');

    // Category and Expense routes would go here, e.g.:
    Route::apiResource('categories', CategoryController::class);

    Route::get('/expenses/byMonth', [ExpensesController::class, 'byMonth'])->name('api.expenses.byMonth');
    Route::apiResource('expenses', ExpensesController::class);

    Route::post('/email/resend', [UserController::class, 'resendVerificationEmail'])
        ->middleware('throttle:3,1')
        ->name('verification.resend');
});

    // Email Verification
 Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])->name('verification.verify');
    
// Protected routes that require email verification
Route::middleware(['auth:api', 'verified'])->group(function () {
    // Add routes here that require email verification
    // Example:
    // Route::apiResource('expenses', ExpenseController::class);
});
