<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

// Route nommée pour le lien de reset dans l'email
Route::get('/auth/reset-password/{token}', function (string $token) {
    return response()->json(['token' => $token]);
})->name('password.reset');

// Routes publiques
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send']);
    Route::post('/reset-password',  [ResetPasswordController::class, 'reset']);
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',                  [AuthController::class, 'logout']);
        Route::get('/me',                       [AuthController::class, 'me']);
        Route::delete('/account',               [AuthController::class, 'destroy']);
        Route::post('/email/verify/send',       [EmailVerificationController::class, 'send']);
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed'])
            ->name('verification.verify');
    });

    // Shifts (gardes)
    Route::get('/shifts',                [ShiftController::class, 'index']);
    Route::post('/shifts',               [ShiftController::class, 'store']);
    Route::get('/shifts/{shift}',        [ShiftController::class, 'show']);
    Route::post('/shifts/{shift}/end',   [ShiftController::class, 'end']);
    Route::delete('/shifts/{shift}',     [ShiftController::class, 'destroy']);

});