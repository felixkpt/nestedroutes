<?php

use App\Models\Sanctum\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

if (!function_exists('checkPermission')) {

    function checkPermission(string $permission, string $method)
    {

        $user = User::find(auth()->id());
        if (!$user) return false;

        if ($method) {
            $roleId = $user->default_role_id ?? (config('nestedroutes.guestRoleId') ?? 0);
            $routePermissions = Role::find($roleId)->getAllPermissions();

            $permissionCleaned = trim(preg_replace('/\./', '/', $permission), '/');

            $httpMethod = Str::upper($method);
            $found = !!collect($routePermissions)->contains(function ($route) use ($permissionCleaned, $httpMethod) {

                return Str::startsWith($route->uri, $permissionCleaned . '@') && Str::contains($route->uri, '@' . $httpMethod);
            });

            return $found;
        } else {
            return $user->can($permission);
        }
    }
}


if (!function_exists('sanctum_auth')) {
    function sanctum_auth()
    {
        // Check if the request contains a Sanctum token
        if ($token = request()->bearerToken()) {
            // Attempt to find the token in the personal access tokens table
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                // Token is valid, authenticate the user
                auth()->login($accessToken->tokenable);
            }
        }
    }
}
