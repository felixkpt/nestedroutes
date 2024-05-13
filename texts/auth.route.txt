<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\Settings\RolePermissions\Roles\RolesController;
use App\Http\Controllers\Dashboard\Settings\RolePermissions\Roles\View\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->group(function () {

    $controller = AuthController::class;

    Route::post('login', [$controller, 'login']);
    Route::post('register', [$controller, 'register']);
    Route::post('password', [$controller, 'passwordResetLink']);

    Route::get('password/{token}', [$controller, 'getEmail'])->name('getEmail');
    Route::post('password-set', [$controller, 'passwordSet'])->name('password.set');

    Route::middleware(['auth:sanctum'])->group(function () use ($controller) {

        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $roles = $user->getRoleNames();
            $user->roles = $roles;
            $user->fileAccessToken = generateTemporaryToken(60);
            return ['results' => $user];
        });

        Route::post('update', [$controller, 'userProfile'])->everyone(true);
        Route::get('profile', [$controller, 'profileShow'])->hidden(true)->everyone(true);
        Route::patch('profile', [$controller, 'profileUpdate'])->hidden(true)->everyone(true);
        Route::patch('update-password', [$controller, 'updatePassword'])->hidden(true)->everyone(true);

        Route::post('logout', [$controller, 'logout']);
    });

    $controller = RolesController::class;
    Route::get('/role-permissions/roles/get-user-roles-and-direct-permissions', [$controller, 'getUserRolesAndDirectPermissions'])->everyone(true)->hidden(true);

    $controller = RoleController::class;
    Route::get('/role-permissions/roles/view/{id}/get-role-menu', [$controller, 'getRoleMenu'])->public(true)->hidden();
    Route::get('/role-permissions/roles/view/{id}/get-role-route-permissions', [$controller, 'getRoleRoutePermissions'])->everyone(true)->hidden();
});
