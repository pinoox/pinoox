<?php

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\App\AppRouter;

final class AppRouteCleanup
{
    /**
     * @return list<string>
     */
    public static function pathsForPackage(string $packageName): array
    {
        $routes = AppRouter::getByPackage($packageName);

        if (!is_array($routes) || $routes === []) {
            return [];
        }

        $paths = [];

        foreach (array_keys($routes) as $path) {
            $path = (string) $path;

            if ($path === '' || $path === '/manager') {
                continue;
            }

            $paths[] = $path;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return list<string>
     */
    public static function deleteForPackage(string $packageName): array
    {
        $deleted = [];

        foreach (self::pathsForPackage($packageName) as $path) {
            if (!AppRouter::exists($path)) {
                continue;
            }

            if (AppRouter::get($path) !== $packageName) {
                continue;
            }

            AppRouter::delete($path);
            $deleted[] = $path;
        }

        return $deleted;
    }
}
