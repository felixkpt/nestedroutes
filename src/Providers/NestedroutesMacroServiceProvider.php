<?php

namespace Felixkpt\Nestedroutes\Providers;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class NestedroutesMacroServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Define the custom macro for the Route class

        // everyone macro
        Route::macro('everyone', function ($value = true) {
            $this->everyone = $value;
            return $this;
        });

        Route::macro('isAccessibleToEveryone', function () {
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

        Route::macro('isHidden', function () {
            try {
                return $this->hidden ?? false;
            } catch (Exception $e) {
                return false;
            }
        });

        // public macro
        Route::macro('public', function ($value = true) {
            $this->public = $value;
            return $this;
        });

        Route::macro('isPublic', function () {
            try {
                return $this->public ?? false;
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
