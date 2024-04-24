<?php

namespace Felixkpt\Nestedroutes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoutesHelper
{
    protected $nested_routes_folder;
    protected $prefix_from;
    protected $renameMainFolders;

    function __construct($prefix_from = null)
    {

        $this->nested_routes_folder = config('nestedroutes.folder');
        $this->renameMainFolders = config('nestedroutes.rename_main_folders');

        $this->prefix_from = trim($prefix_from ? $prefix_from : '', '/');
    }

    /**
     * Get nested routes from a specific folder.
     *
     * @param string $folder The folder name.
     * @return array An array containing information about the nested routes.
     */
    public function getRoutes($leftTrim)
    {

        $routes_path = base_path('routes/' . $this->nested_routes_folder);

        if (file_exists($routes_path)) {

            $folder = $routes_path;
            $routes = $this->getRoutesReal($folder, $routes_path, $leftTrim);

            $foldermain = $this->getFolderAfterNested($folder);

            $item = [
                'folder' => ($this->renameMainFolders[$foldermain] ?? $foldermain),
                'children' => [],
                'routes' => $routes,
                'hidden' => $this->getHidden($foldermain),
                'icon' => $this->getIcon($foldermain),
                'position' => $this->getPosition($foldermain),
            ];


            $items = $this->iterateFolders($folder, $routes_path, $leftTrim, true);

            array_unshift($items, $item);

            // Sort the items array based on position
            usort($items, function ($a, $b) {
                return $a['position'] - $b['position'];
            });


            // Sort the children of each folder based on position
            foreach ($items as &$item) {
                usort($item['children'], function ($a, $b) {
                    return $a['position'] - $b['position'];
                });
            }

            return $items;
        }

        return null;
    }

    private function iterateFolders($folder, $routes_path, $leftTrim, $is_main = false)
    {

        $items = [];
        $folders = File::directories($folder);

        foreach ($folders as $folder) {

            $permissions_ignored_folders = config('nested_routes.permissions.ignored_folders') ?? [];
            if ($is_main && in_array($folder, $permissions_ignored_folders)) {
                continue;
            }

            $routes = $this->getRoutesReal($folder, $routes_path, $leftTrim);

            $foldermain = $this->getFolderAfterNested($folder);

            $item = [
                'folder' => $is_main ? ($this->renameMainFolders[$foldermain] ?? $foldermain) : $foldermain,
                'children' => $this->iterateFolders($folder, $routes_path, $leftTrim),
                'routes' => $routes,
                'hidden' => $this->getHidden($foldermain),
                'icon' => $this->getIcon($foldermain),
                'position' => $this->getPosition($foldermain),
            ];

            $items[] = $item;
        }

        return $items;
    }

    function getHidden($folder)
    {
        return Permission::where('name', $folder)->first()->hidden ?? null;
    }

    function getIcon($folder)
    {
        return Permission::where('name', $folder)->first()->icon ?? null;
    }

    function getPosition($folder)
    {
        return Permission::where('name', $folder)->first()->position ?? 999999;
    }

    function getRoutesReal($folder, $routes_path, $leftTrim)
    {
        $items = [];
        // Filter out the driver.php files and process each route file
        $route_files = collect(File::files($folder))->filter(function ($file) {
            $filename = $file->getFileName();
            return !Str::is($filename, 'driver.php') &&
                !Str::is($filename, 'auth.route.php') && // Exclude auth.route.php
                Str::endsWith($filename, '.route.php');
        });

        foreach ($route_files as $file) {

            // Handle the route file and extract relevant information
            $res = $this->handle($file, $routes_path, $leftTrim);

            // dump($res);

            $prefix = $res['prefix'];
            $file_path = $res['file_path'];
            $folder_after_nested = $res['folder_after_nested'];

            // Get the existing routes before adding new ones
            $existingRoutes = collect(Route::getRoutes())->pluck('uri');

            Route::group(['prefix' => $prefix], function () use ($file_path, $existingRoutes, $folder_after_nested, &$items) {

                require $file_path;

                $filename = Str::title(Str::before(basename($file_path), '.route.php'));

                // Get the newly added routes and their corresponding folders
                $routes = collect(Route::getRoutes())->filter(function ($route) use ($existingRoutes) {
                    return !in_array($route->uri, $existingRoutes->toArray());
                })->map(function ($route) use ($folder_after_nested, $filename) {

                    $uri = $route->uri;

                    $methods = '@' . implode('|@', $route->methods());
                    $uri_methods = $uri . $methods;

                    $slug = Str::slug(Str::replace('/', '.', $uri), '.');

                    $parts = explode('/', $uri);
                    $title = end($parts);

                    if (isset($route->action['controller'])) {
                        $c = explode('@', $route->action['controller']);
                        if (count($c) === 2) {
                            $title = $c[1];
                        }
                    }

                    if ($route->getName()) {
                        $parts = preg_replace('#\.#', ' ', $route->getName());
                        $title = Str::title($parts);
                    }

                    // Convert camel case to words
                    $words = preg_split('/(?=[A-Z])/', $title, -1, PREG_SPLIT_NO_EMPTY);

                    // Capitalize the first letter of each word and join them with spaces
                    $title = implode(' ', array_map(fn ($word) => ucfirst($word), $words));
                    $title = Str::title($title);

                    $checked = $route->isAccessibleToEveryone() !== false ? $route->isAccessibleToEveryone() : $route->isPublic();

                    return [
                        'uri' => $uri,
                        'methods' => $methods,
                        'uri_methods' => $uri_methods,
                        'slug' => $slug,
                        'title' => $title,
                        'folder' => $folder_after_nested,
                        'hidden' => $route->isHidden(),
                        'icon' => $route->getIcon(),
                        'checked' => $checked,
                        'is_public' => $route->isPublic(),
                        'filename' => $filename,
                    ];
                });

                $items = array_merge($items, $routes->toArray());
            });
        }

        // dd(33);

        return $items;
    }

    /**
     * Handle the processing of a route file and extract relevant information.
     *
     * @param \SplFileInfo $file The route file.
     * @param string $this->nested_routes_folder The folder name after 'nested-routes'.
     * @param string $routes_path The base path to the routes folder.
     * @param bool $get_folder_after_nested Whether to get the folder name after the 'nested-routes' folder.
     * @return array The processed route information as an associative array.
     */
    function handle($file, $get_folder_after = null)
    {

        $path = $file->getPath();

        $get_folder_after = true;

        $folder_after_nested = null;
        if ($get_folder_after)
            $folder_after_nested = $this->getFolderAfterNested($path, $this->nested_routes_folder);

        $file_name = $file->getFileName();
        $prefix = $file_name;

        $prefix = $this->getPrefix($file);

        $file_path = $file->getPathName();
        $res = [
            'prefix' => $prefix,
            'file_path' => $file_path,
            'folder_after_nested' => $folder_after_nested,
        ];

        return $res;
    }

    function getPrefix($file)
    {

        $file_name = $file->getBaseName();

        $path = $file->getPath();

        $dirname = Str::afterLast($path, $file_name);
        $prefix = Str::afterLast($dirname, $this->nested_routes_folder);

        $replaced_filename = Str::replace('index.route.php', '', $file_name);

        $replaced_filename = Str::replace('.route.php', '', $replaced_filename);

        $file_name = $replaced_filename;
        if (Str::endsWith($prefix, $replaced_filename)) {
            $file_name = '';
        }

        if ($file_name) {
            $file_name = '/' . $file_name;
        }

        $prefix = strtolower($prefix . $file_name);

        if (!Str::startsWith(trim($prefix, '/'), $this->prefix_from)) {
            $prefix = '/' . $this->prefix_from . $prefix;
        }

        return $prefix;
    }

    /**
     * Get the folder name after the nested-routes folder.
     *
     * @param string $path The full path to the route file.
     * @param string $this->nested_routes_folder The folder name after 'nested-routes'.
     * @return string|null The folder name after 'nested-routes', or null if not found.
     */
    function getFolderAfterNested($path)
    {
        $parts = explode('/', $this->nested_routes_folder);
        $folder_after_nested = null;

        $this->nested_routes_folder = trim($this->nested_routes_folder, '/');

        $start_position = strpos($path, $this->nested_routes_folder);

        if ($start_position !== false) {
            $start_position += strlen($this->nested_routes_folder) + 1; // Adding 1 to skip the slash after the folder name.
            $folder_after_nested = substr($path, $start_position);
        }

        // Loop through all parts of $this->nested_routes_folder and handle empty parts
        foreach ($parts as $part) {
            if (!empty($part)) {
                $folder_after_nested = str_replace($part, '', $folder_after_nested, $count);
                if ($count > 0) {
                    break;
                }
            }
        }

        if (!$folder_after_nested) $folder_after_nested = $part;

        return $folder_after_nested;
    }
}
