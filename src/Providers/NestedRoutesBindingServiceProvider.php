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

        // Get the folder name after 'nested-routes' from environment configuration
        $admin_routes_folder = preg_replace('#/+#', '/', env('ADMIN_NESTED_ROUTES_FOLDER', 'nested-routes/admin'));
        $client_routes_folder = preg_replace('#/+#', '/', env('CLIENT_NESTED_ROUTES_FOLDER', 'nested-routes/client'));

        // Set the 'nested_routes.admin_folder' configuration key to the value of 'ADMIN_NESTED_ROUTES_FOLDER/CLIENT_NESTED_ROUTES_FOLDER'
        config([
            'nested_routes.admin_folder' => $admin_routes_folder,
            'nested_routes.client_folder' => $client_routes_folder,
        ]);

        $this->app->register(NestedRoutesMacroServiceProvider::class);
        $this->app->register(NestedRoutesServiceProvider::class);
    }
}
