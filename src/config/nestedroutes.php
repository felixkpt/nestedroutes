<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nested Routes Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls the behavior of nested routes.
    |
    */

    'folder' => 'nested-routes', // Folder where nested routes are stored
    'prefix' => 'api', // Prefix for nested routes
    'middlewares' => ['api', 'nesteroutes.auth'], // Middlewares applied to nested routes
    'permissions' => [
        'ignored_folders' => env('permissions_ignored_folders', [
            'auth', // Ignored folders for permissions
            'client',
        ]),
    ],
    'rename_root_folders' => [
        'admin' => 'dashboard' // Rename root folders for nested routes
    ],
    'expanded_root_folders' => [], // Expanded root folders for nested routes
    // If the key is not provided, it means the website doesn't support guest mode,
    // all unauthenticated requests will be redirected to login
    'guestRoleId' => 1101,
];
