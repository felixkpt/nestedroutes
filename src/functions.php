<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

if (!function_exists('checkPermission')) {

    function checkPermission(string $permission, string $method)
    {

        $user = User::find(auth()->id());

        if ($method) {
            $routePermissions = Role::find($user->default_role_id)->getAllPermissions();

            Log::info($routePermissions);
            $permission = preg_replace('/\./', '/', $permission);
            $permissionCleaned = $permission == '/' ? 'admin' : preg_replace('/\/$/', '', Str::afterLast($permission, 'admin/'));

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
