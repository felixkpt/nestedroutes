<?php

namespace Felixkpt\Nestedroutes\Providers;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class NestedRoutesMacroServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Define the custom macro for the Route class

        // hidden macro
        Route::macro('everyone', function ($value = true) {
            $this->everyone = $value;
            return $this;
        });

        Route::macro('everyoneRoute', function () {
            try {
                return $this->everyone ?? false;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // hidden macro
        Route::macro('hidden', function ($value = true) {
            $this->hidden = $value;
            return $this;
        });

        Route::macro('hiddenRoute', function () {
            try {
                return $this->hidden ?? false;
            } catch (Exception $e) {
                return false;
            }
        });


        // icon macro
        Route::macro('icon', function ($value = true) {
            $this->icon = $value;
            return $this;
        });

        Route::macro('getIcon', function () {
            try {
                return $this->icon ?? null;
            } catch (Exception $e) {
                return null;
            }
        });
    }
}
