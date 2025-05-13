<?php

namespace Felixkpt\Nestedroutes\Providers;

use Felixkpt\Nestedroutes\Console\Commands\PublishMigrationFilesCommand;
use Felixkpt\Nestedroutes\Http\Middleware\NestedroutesAuthMiddleware;
use Felixkpt\Nestedroutes\Http\Middleware\TemporaryTokenValidationMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
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

        $router = $this->app->make(Router::class);
        $this->app['router']->middleware('nestedroutes.auth', NestedroutesAuthMiddleware::class);

        $router->middleware('nestedroutes.auth', NestedroutesAuthMiddleware::class);
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
        $folder = 'database/migrations';

        $this->publishFiles($folder, 'nestedroutes-migrations', true);
    }

    protected function publishModels()
    {
        $srcFolder = __DIR__ . '/../app/Models';
        $dstFolder = app_path('/Models');

        $this->publishFolders($srcFolder, $dstFolder, 'nestedroutes-models');
    }

    protected function publishFolders($srcFolder, $dstFolder, $tag)
    {
        $filesArray = [];

        // Ensure source directory exists
        if (!File::isDirectory($srcFolder)) {
            return;
        }

        // Ensure destination directory exists, create it if not
        if (!File::isDirectory($dstFolder)) {
            File::makeDirectory($dstFolder, 0755, true, true);
        }

        // Iterate over directories in source directory
        $directories = File::directories($srcFolder);
        foreach ($directories as $directory) {
            $relativePath = Str::after($directory, $srcFolder);
            $destinationDirectory = $dstFolder . $relativePath;

            // Recursively copy subdirectories
            $this->publishFolders($directory, $destinationDirectory, $tag);
        }

        // Iterate over files in source directory
        $files = File::files($srcFolder);
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $sourceFile = $file->getPathname();
            $relativePath = Str::after($sourceFile, $srcFolder);

            // Copy the file to the destination
            $destination = $dstFolder . '/' . $fileName;
            $existingFile = $this->fileExists($fileName);
            $filesArray[$sourceFile] = $existingFile ?? $destination;
        }

        // Publish the files array
        $this->publishes($filesArray, $tag);
    }

    protected function publishFiles($folder, $tag, $timestamp = false)
    {
        $filesArray = [];

        $src = $folder;
        foreach (File::files($src) as $file) {
            $fileName = $file->getFilename();
            $fileWithPath = $src . '/' . $fileName;
            $destination = base_path($folder . '/' . ($timestamp ? Carbon::now()->addSeconds(10)->format('Y_m_d_His') . '_' . $fileName : $fileName));
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
    protected function fileExists($fileName)
    {
        // If the file already exists in destination, skip copying
        return File::exists($fileName) ? $fileName : null;
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
