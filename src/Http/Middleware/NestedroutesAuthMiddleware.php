<?php

namespace Felixkpt\Nestedroutes\Http\Middleware;

use App\Models\Permission;
use App\Models\Role;
use Closure;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class NestedroutesAuthMiddleware
{

    protected $router;

    protected $path;
    protected $user;
    protected $menus;
    protected $allow = false;
    protected $request;
    protected $is_app = 0;
    protected $common;
    protected $userPermissions;
    protected $allPermissionsFile;

    protected $allowedPermissions;
    protected $role;
    protected $urls = [];
    protected $loopLevel = 0;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // Check if the request contains a Sanctum token
        if ($token = $request->bearerToken()) {
            // Attempt to find the token in the personal access tokens table
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                // Token is valid, authenticate the user
                Auth::login($accessToken->tokenable);
            }
        }

        // Set up necessary data for authorization checks...
        if ($request) {
            $this->request = $request;
            $this->user = auth()->user();

            // For testing purposes only enable the below user if you are accessing directly from the browser
            if (!$this->user) {
                // $this->user = User::first();
            }

            $this->path = Route::getFacadeRoot()->current()->uri();
        }

        // Perform checks for authorization...
        $this->authorize();

        return $next($request);
    }

    /**
     * Perform authorization checks for the incoming request.
     *
     * @return mixed
     */
    protected function authorize()
    {

        $currentRoute = rtrim(request()->getPathInfo(), '/');
        $permissions_ignored_folders = config('nested_routes.permissions.ignored_folders') ?? [];

        $top_level = trim(Str::after($currentRoute, '/api'), '/');
        $top_level = Str::before($top_level, '/');

        if (in_array($top_level, $permissions_ignored_folders)) {
            return true;
        }

        // Allow certain routes without authorization...
        if (Str::startsWith($currentRoute, '/api/client')) {
            return true;
        }

        // Define routes that are allowed without specific permissions...
        $allowedRoutes = [
            '/',
            'auth/user',
            'auth/password',
            '/api/admin/settings/role-permissions/roles/get-user-roles-and-direct-permissions',
            '/api/admin/settings/role-permissions/roles/view/{id}/get-role-menu',
            '/api/admin/settings/role-permissions/roles/view/{id}/get-role-route-permissions',
            '/api/admin/file-repo/*',
        ];

        // Check if the current route matches any of the allowed routes
        $allowed = collect($allowedRoutes)->contains(function ($allowedRoute) use ($currentRoute) {

            if (Str::endsWith($allowedRoute, '*') && Str::startsWith($currentRoute, Str::replaceLast('*', '', $allowedRoute))) return true;

            return preg_match("#^" . str_replace(['/', '{id}'], ['\/', '\d+'], $allowedRoute) . "$#", $currentRoute);
        });

        if ($allowed) return true;

        // handling publi routes
        $public_permissions = Permission::where('is_public', true)->pluck('uri');

        [$authenticate, $found_path] = $this->findRouteAndAuthenticate($public_permissions);

        if ($authenticate) return true;

        if (!$this->user) {
            App::abort(401, "Unauthenticated.");
        }

        // Retrieve permissions inherited from the user's default_role_id
        $role = Role::find($this->user->default_role_id);
        if ($role) {
            $permissions = $role->permissions->pluck('uri') ?? [];
        } else {
            abort(404, 'User default role not found!');
        }

        [$authenticate, $found_path] = $this->findRouteAndAuthenticate($permissions);

        if ($authenticate) {
            return true;
        }

        // If the route is found but the method is not allowed...
        if ($found_path === true) {
            $this->unauthorize(405);
        }

        return $this->unauthorize();
    }

    function findRouteAndAuthenticate($permissions)
    {
        // Get the current route and request method...
        $incoming_route = Str::after(Route::getCurrentRoute()->uri, 'api/');
        $method = request()->method();

        $found_path = false;
        $authenticate = false;
        foreach ($permissions as $uri) {
            // Split the URI into route and methods...
            $res = preg_split('#@#', $uri, 2);
            $curr_route = $res[0];

            $methods = array_filter(explode('@', str_replace('|', '', $res[1] ?? '')));

            $methods = [...$methods];

            // Check if the current route and method match the user's permissions...
            if ($incoming_route == $curr_route) {
                $found_path = true;

                if (in_array($method, $methods) || in_array('any', $methods)) {
                    $authenticate = true;
                    continue;
                }
            }
        }

        return [$authenticate, $found_path];
    }

    /**
     * Abort the request with an unauthorized status and message.
     *
     * @param  int  $status
     * @param  string|null  $message
     * @return void
     */
    public function unauthorize($status = 403, $message = null)
    {
        App::abort($status, ($status === 405 ? "Not authorized to perform the current method on" : "Not authorized to access") . " this page/resource/endpoint");
    }
}
