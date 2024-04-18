<?php

namespace App\Services\Nestedroutes;

use Illuminate\Support\Str;

class GetNestedroutes
{
    protected $prefix;
    protected $leftTrim;

    /**
     * Create a new GetNestedroutes instance.
     *
     * @param string $prefix The prefix to use for nested routes (default: 'admin').
     * @param string $leftTrim The part of the URL to trim from the left (default: 'admin').
     */
    public function __construct($prefix = 'admin', $leftTrim = 'admin')
    {
        $this->prefix = null;
        $this->leftTrim = null;

        // If $leftTrim is empty, generate a random string to avoid potential issues
        if (strlen($leftTrim) < 1) {
            $this->leftTrim = Str::random();
        }
    }

    /**
     * Get the list of nested routes grouped by folder.
     *
     * @return array The grouped nested routes.
     */
    public function list()
    {
        // Get all the nested routes from the 'nested-routes/admin' folder
        $nestedRoutes = (new RoutesHelper())->getRoutes($this->leftTrim);

        // // Group the routes by folder hierarchy
        // $nestedRoutes = $this->groupRoutesByFolder($nestedRoutes);

        return $nestedRoutes;
    }

    function groupRoutesByFolder($routes)
    {
        $groupedRoutes = [];

        foreach ($routes as $route) {
            $folderParts = explode('/', $route['folder']);
            $currentGroup = &$groupedRoutes;

            foreach ($folderParts as $folderPart) {
                if (!isset($currentGroup[$folderPart])) {
                    $currentGroup[$folderPart] = [
                        'routes' => [],
                        'children' => []
                    ];
                }
                $currentGroup = &$currentGroup[$folderPart]['children'];
            }

            $currentGroup['routes'][] = $route;
            if (isset($currentGroup['children']))
                $currentGroup['children'] = $this->groupRoutesByFolder($currentGroup['children']);
        }

        return $groupedRoutes;
    }


    function printRoutes($routes, $indent = 0)
    {
        $indentation = str_repeat('    ', $indent);

        foreach ($routes as $folder => $data) {
            echo $indentation . $folder . PHP_EOL;

            if (!empty($data['routes'])) {
                foreach ($data['routes'] as $route) {
                    echo $indentation . '    ' . $route['uri'] . PHP_EOL;
                }
            }

            if (!empty($data['children'])) {
                $this->printRoutes($data['children'], $indent + 1);
            }
        }
    }
}
