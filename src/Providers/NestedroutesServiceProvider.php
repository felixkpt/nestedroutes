<?php

namespace Felixkpt\Nestedroutes\Providers;

use Felixkpt\Nestedroutes\Http\Middleware\NestedroutesAuthMiddleware;
use Felixkpt\Nestedroutes\Http\Middleware\TemporaryTokenValidationMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class NestedroutesServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(NestedroutesMacroServiceProvider::class);

        // Helper functions include
        require_once __DIR__ . '/../functions.php';
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('nestedroutes.auth', NestedroutesAuthMiddleware::class);
        $router->aliasMiddleware('nestedroutes.temporary_token', TemporaryTokenValidationMiddleware::class);

        $this->configureDefaults();

        $folder = base_path(preg_replace('@/+@', '/', 'routes/nested-routes/'));
        File::ensureDirectoryExists($folder);

        $driver = preg_replace('@/+@', '/', $folder . '/driver.php');

        $this->loadRoutesFrom($driver);
    }

    public function configureDefaults()
    {

        $folder = base_path(preg_replace('@/+@', '/', 'routes/nested-routes/'));
        File::ensureDirectoryExists($folder);

        $driver = preg_replace('@/+@', '/', $folder . '/driver.php');

        if (!file_exists($driver)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/driver.txt', 'r');
            File::put($driver, $contents);
            chmod($driver, 775);
        }

        $auth = preg_replace('@/+@', '/', $folder . '/auth.route.php');
        if (!file_exists($auth)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/auth.route.txt', 'r');
            File::put($auth, $contents);
            chmod($auth, 775);
        }


        $folder = base_path(preg_replace('@/+@', '/', 'app/Http/Controllers/Auth'));
        File::ensureDirectoryExists($folder);

        $auth_controller = preg_replace('@/+@', '/', $folder . '/AuthController.php');
        if (!file_exists($auth_controller)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/Auth/AuthController.txt', 'r');
            File::put($auth_controller, $contents);
            chmod($auth_controller, 775);
        }

        $folder = config_path();

        $auth_controller = preg_replace('@/+@', '/', $folder . '/nestedroutes.php');
        if (!file_exists($auth_controller)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/nestedroutes.txt', 'r');
            File::put($auth_controller, $contents);
            chmod($auth_controller, 775);
        }
    }
}
