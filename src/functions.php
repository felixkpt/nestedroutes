<?php

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

if (!function_exists('checkPermission')) {

    function checkPermission(string $permission, string $method)
    {

        $user = User::find(auth()->id());

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
