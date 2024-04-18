<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->group(function () {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('password', [AuthController::class, 'passwordResetLink']);

    Route::get('password/{token}', [AuthController::class, 'getEmail'])->name('getEmail');
    Route::post('password-set', [AuthController::class, 'passwordSet'])->name('password.set');

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $roles = $user->getRoleNames();
            $user->roles = $roles;
            $user->fileAccessToken = generateTemporaryToken(60);
            return ['results' => $user];
        });

        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('abilities', [AuthController::class, 'abilities']);
    });
});
