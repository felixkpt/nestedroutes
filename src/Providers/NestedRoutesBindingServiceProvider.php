<?php

namespace Felixkpt\Nestedroutes\Providers;

use Illuminate\Support\ServiceProvider;

class NestedRoutesBindingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->configure();

        $this->app->register(NestedRoutesMacroServiceProvider::class);
        $this->app->register(NestedRoutesServiceProvider::class);
    }

    function boot()
    {
        $this->registerMiddleware();
        $this->configure();
    }

    /**
     * Register the middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        // Register middleware using the 'middleware' method
        $this->app['router']->middleware([
            'nestedroutes.auth' => \Felixkpt\Nestedroutes\Http\Middleware\NestedRoutesAuthMiddleware::class,
        ]);
    }

    public function configure()
    {
        $configPath = config_path('nestedroutes.php');

        // Check if the config file already exists
        if (!file_exists($configPath)) {
            // If not, create it with default configuration
            $this->createDefaultConfig($configPath);
        }
    }

    private function createDefaultConfig($configPath)
    {
        $defaultConfig = [
            'folder' => 'nested-routes',
            'permissions' => [
                'ignored_folders' => env('permissions_ignored_folders', [
                    'auth',
                    'client',
                ]),
            ],
            'rename_main_folders' => [
                'admin' => 'dashboard'
            ]
        ];

        // Convert array to PHP code
        $configContent = "<?php\n\nreturn " . var_export($defaultConfig, true) . ";\n";

        // Write config content to file
        file_put_contents($configPath, $configContent);
    }
}
