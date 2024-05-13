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
        $this->ensureNestedRoutesDirectoryExists();
        $this->createDefaultRouteFiles();
        $this->createDefaultAuthController();
        $this->createDefaultConfigFile();
        $this->createDefaultModels();
    }

    private function ensureNestedRoutesDirectoryExists()
    {
        $folder = base_path(preg_replace('@/+@', '/', 'routes/nested-routes/'));
        File::ensureDirectoryExists($folder);
    }

    private function createDefaultRouteFiles()
    {
        $this->createDefaultFile('driver.php', 'driver.txt', '/../../texts/');
        $this->createDefaultFile('auth.route.php', 'auth.route.txt', '/../../texts/');
    }

    private function createDefaultAuthController()
    {
        $folder = base_path(preg_replace('@/+@', '/', 'app/Http/Controllers/Auth'));
        File::ensureDirectoryExists($folder);
        $this->createDefaultFile('AuthController.php', 'Auth/AuthController.txt', '/../../texts/');
    }

    private function createDefaultConfigFile()
    {
        $folder = config_path();
        $this->createDefaultFile('nestedroutes.php', 'nestedroutes.txt', '/../../texts/');
    }

    private function createDefaultFile($fileName, $templateFile, $templatePath)
    {
        $filePath = preg_replace('@/+@', '/', base_path("routes/nested-routes/$fileName"));
        if (!file_exists($filePath)) {
            $contents = file_get_contents(__DIR__ . "$templatePath$templateFile", 'r');
            File::put($filePath, $contents);
            chmod($filePath, 775);
        }
    }

    private function createDefaultModels()
    {
        $packageModelsPath = __DIR__ . '/../../Models';
        $appModelsPath = app_path('Models');

        if (File::isDirectory($packageModelsPath)) {
            File::copyDirectory($packageModelsPath, $appModelsPath);
        }
    }
}
