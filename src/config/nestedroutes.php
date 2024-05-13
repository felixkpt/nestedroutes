<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nested Routes config
    |--------------------------------------------------------------------------
    |
    | This option controls the nested routes behavior.
    |
    */
    'folder' => 'nested-routes',
    'prefix' => 'api',
    'middleWares' => ['api', 'nesteroutes.auth'],
    'permissions' => [
        'ignored_folders' => env('permissions_ignored_folders', [
            'auth',
            'client',
        ]),
    ],

    'rename_main_folders' => [
        'admin' => 'dashboard',
    ],
    'guestRoleId' => 2,
    'defaultPublicRoutes' => [
        'dashboard/settings/role-permissions/roles/get-user-roles-and-direct-permissions',
        'dashboard/settings/role-permissions/roles/view/{id}/get-role-menu',
        'dashboard/settings/role-permissions/roles/view/{id}/get-role-route-permissions',
        'file-repo/*',
    ]
];
