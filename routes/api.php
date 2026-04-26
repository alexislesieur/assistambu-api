<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/reset-password/{token}', function (string $token) {
    return response()->json(['token' => $token]);
})->name('password.reset');

Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send']);
    Route::post('/reset-password',  [ResetPasswordController::class, 'reset']);
});

Route::post('/waitlist', [WaitlistController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout',              [AuthController::class, 'logout']);
        Route::get('/me',                   [AuthController::class, 'me']);
        Route::delete('/account',           [AuthController::class, 'destroy']);
        Route::post('/email/verify/send',   [EmailVerificationController::class, 'send']);
    });

    Route::get('/user',          [UserController::class, 'show']);
    Route::put('/user',          [UserController::class, 'update']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    Route::get('/waitlist',         [WaitlistController::class, 'index']);
    Route::post('/waitlist/admin',  [WaitlistController::class, 'adminStore']);
    Route::delete('/waitlist/{id}', [WaitlistController::class, 'destroy']);

    // Catégories accessibles à tous
    Route::get('/intervention-categories', [AdminController::class, 'interventionCategories']);
    Route::get('/item-categories',         [AdminController::class, 'itemCategories']);

    Route::get('/shifts',                   [ShiftController::class, 'index']);
    Route::post('/shifts',                  [ShiftController::class, 'store']);
    Route::get('/shifts/{shift}',           [ShiftController::class, 'show']);
    Route::post('/shifts/{shift}/end',      [ShiftController::class, 'end']);
    Route::delete('/shifts/{shift}',        [ShiftController::class, 'destroy']);

    Route::get('/interventions',                    [InterventionController::class, 'index']);
    Route::post('/interventions',                   [InterventionController::class, 'store']);
    Route::get('/interventions/{intervention}',     [InterventionController::class, 'show']);
    Route::put('/interventions/{intervention}',     [InterventionController::class, 'update']);
    Route::delete('/interventions/{intervention}',  [InterventionController::class, 'destroy']);
    Route::get('/shifts/{shift}/interventions',     [InterventionController::class, 'byShift']);

    Route::get('/items',                    [ItemController::class, 'index']);
    Route::post('/items',                   [ItemController::class, 'store']);
    Route::get('/items/alerts',             [ItemController::class, 'alerts']);
    Route::get('/items/{item}',             [ItemController::class, 'show']);
    Route::put('/items/{item}',             [ItemController::class, 'update']);
    Route::delete('/items/{item}',          [ItemController::class, 'destroy']);
    Route::post('/items/{item}/restock',    [ItemController::class, 'restock']);

    Route::get('/hospitals',                [HospitalController::class, 'index']);
    Route::get('/hospitals/{hospital}',     [HospitalController::class, 'show']);
    Route::post('/hospitals',               [HospitalController::class, 'store']);
    Route::put('/hospitals/{hospital}',     [HospitalController::class, 'update']);
    Route::delete('/hospitals/{hospital}',  [HospitalController::class, 'destroy']);

    Route::get('/schedules',                [ScheduleController::class, 'index']);
    Route::get('/schedules/month',          [ScheduleController::class, 'byMonth']);
    Route::get('/schedules/week',           [ScheduleController::class, 'byWeek']);
    Route::post('/schedules',               [ScheduleController::class, 'store']);
    Route::get('/schedules/{schedule}',     [ScheduleController::class, 'show']);
    Route::put('/schedules/{schedule}',     [ScheduleController::class, 'update']);
    Route::delete('/schedules/{schedule}',  [ScheduleController::class, 'destroy']);

    Route::get('/stats/day',   [StatsController::class, 'day']);
    Route::get('/stats/week',  [StatsController::class, 'week']);
    Route::get('/stats/month', [StatsController::class, 'month']);

    // ===== ADMIN =====
    Route::prefix('admin')->group(function () {
        Route::get('/stats',                        [AdminController::class, 'stats']);
        Route::get('/users',                        [AdminController::class, 'users']);
        Route::put('/users/{user}',                 [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}',              [AdminController::class, 'destroyUser']);
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPasswordUser']);
        Route::get('/shifts',                       [AdminController::class, 'shifts']);
        Route::get('/interventions',                [AdminController::class, 'interventions']);
        Route::get('/items',                        [AdminController::class, 'items']);
        Route::get('/logs',                         [AdminController::class, 'logs']);

        // Catégories interventions
        Route::get('/intervention-categories',                              [AdminController::class, 'interventionCategories']);
        Route::post('/intervention-categories',                             [AdminController::class, 'storeInterventionCategory']);
        Route::put('/intervention-categories/{category}',                   [AdminController::class, 'updateInterventionCategory']);
        Route::delete('/intervention-categories/{category}',                [AdminController::class, 'destroyInterventionCategory']);

        // Catégories articles
        Route::get('/item-categories',                                      [AdminController::class, 'itemCategories']);
        Route::post('/item-categories',                                     [AdminController::class, 'storeItemCategory']);
        Route::put('/item-categories/{itemCategory}',                       [AdminController::class, 'updateItemCategory']);
        Route::delete('/item-categories/{itemCategory}',                    [AdminController::class, 'destroyItemCategory']);
    });

});