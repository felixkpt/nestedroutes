<?php

namespace Felixkpt\Nestedroutes\Providers;

use Felixkpt\Nestedroutes\Http\Middleware\NestedRoutesAuthMiddleware;
use Felixkpt\Nestedroutes\Http\Middleware\TemporaryTokenValidationMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class NestedRoutesServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('nested_routes_auth', NestedRoutesAuthMiddleware::class);
        $router->aliasMiddleware('temporary_token', TemporaryTokenValidationMiddleware::class);

        $driver = preg_replace('@/+@', '/', 'routes/nested-routes/driver.php');
        $this->loadRoutesFrom(base_path($driver));
    }
}
