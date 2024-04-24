<?php

namespace Felixkpt\Nestedroutes\Providers;

use Felixkpt\Nestedroutes\Console\Commands\PublishMigrationFilesCommand;
use Felixkpt\Nestedroutes\Http\Middleware\NestedroutesAuthMiddleware;
use Felixkpt\Nestedroutes\Http\Middleware\TemporaryTokenValidationMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('nestedroutes.auth', NestedroutesAuthMiddleware::class);
        $router->aliasMiddleware('nestedroutes.temporary_token', TemporaryTokenValidationMiddleware::class);

        // Helper functions include
        require_once __DIR__ . '/../functions.php';

        // Register custom command
        $this->commands([
            PublishMigrationFilesCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->ensureDefaultsExist();
        $this->loadNestedRoutes();
        $this->registerPublishing();
    }

    public function ensureDefaultsExist()
    {
        $folder = base_path(preg_replace('@/+@', '/', 'routes/nested-routes/'));
        File::ensureDirectoryExists($folder);

        $driver = preg_replace('@/+@', '/', $folder . '/driver.php');

        if (!file_exists($driver)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/driver.txt', 'r');
            File::put($driver, $contents);
            chmod($driver, 775);
        }

        $path = preg_replace('@/+@', '/', $folder . '/auth.route.php');
        if (!file_exists($path)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/auth.route.txt', 'r');
            File::put($path, $contents);
            chmod($path, 775);
        }

        $folder = base_path(preg_replace('@/+@', '/', 'app/Http/Controllers/Auth'));
        File::ensureDirectoryExists($folder);

        $path = preg_replace('@/+@', '/', $folder . '/AuthController.php');
        if (!file_exists($path)) {
            $contents = file_get_contents(__DIR__ . '/../../texts/Auth/AuthController.txt', 'r');
            File::put($path, $contents);
            chmod($path, 775);
        }
    }

    protected function loadNestedRoutes()
    {
        $folder = base_path(preg_replace('@/+@', '/', 'routes/nested-routes/'));
        File::ensureDirectoryExists($folder);
        $driver = preg_replace('@/+@', '/', $folder . '/driver.php');
        $this->loadRoutesFrom($driver);
    }

    protected function registerPublishing()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishConfig();
        $this->publishMigrations();
        $this->publishModels();
    }

    protected function publishConfig()
    {
        $this->publishes(
            [
                __DIR__ . '/../config/nestedroutes.php' => config_path('nestedroutes.php'),
            ],
            'nestedroutes-config'
        );
    }

    protected function publishMigrations()
    {
        $folder = __DIR__ . '/../database/migrations/';

        $this->publishFiles($folder, 'nestedroutes-migrations');
    }

    protected function publishModels()
    {
        $folder = __DIR__ . '/../Models/';

        $this->publishFiles($folder, 'nestedroutes-models');
    }

    protected function publishFiles($folder, $tag)
    {
        $filesArray = [];

        foreach (File::files($folder) as $file) {
            $fileName = $file->getFilename();
            $fileWithPath = $folder . $fileName;
            $destination = app_path('Models/' . $fileName);
            $existingFile = $this->fileExistsEndingWith($fileName);
            $filesArray[$fileWithPath] = $existingFile ?? $destination;
        }

        $this->publishes($filesArray, $tag);
    }

    /**
     * Check if any files in the destination directory end with the specified file name.
     * If found, return the path of the existing file, otherwise return null.
     *
     * @param string $fileName
     * @return string|null
     */
    protected function fileExistsEndingWith($fileName)
    {
        $files = File::glob(database_path('migrations/*' . $fileName));

        if (!empty($files)) {
            return $files[0]; // Return the path of the first found file
        }

        return null; // No file found
    }
}
