<?php

namespace Pinoox\Component\Package\Routing;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Server\WebServerFix;

final class FrontControllerAppResolver
{
    /** @var list<string> */
    private const PREFERRED_PACKAGES = [
        'com_pinoox_installer',
        'com_pinoox_manager',
        'com_pinoox_comingsoon',
    ];

    public static function resolve(AppRouter $router, string $pathInfo): ?AppLayer
    {
        if (!WebServerFix::isRootFixPath($pathInfo)) {
            return null;
        }

        $routes = $router->routes();
        $isStable = static fn (string $package): bool => $router->stable($package);

        $fromReferer = self::matchFromReferer($router->getRequest(), $routes, $isStable, $pathInfo);

        if ($fromReferer !== null) {
            return $fromReferer;
        }

        $mounted = self::mountedApps($routes, $isStable);

        if ($mounted === []) {
            return null;
        }

        if (count($mounted) === 1) {
            return self::layer($mounted[0], 'front_controller_singleton', $pathInfo);
        }

        foreach (self::PREFERRED_PACKAGES as $package) {
            foreach ($mounted as $candidate) {
                if ($candidate['package'] === $package) {
                    return self::layer($candidate, 'front_controller_default', $pathInfo);
                }
            }
        }

        return self::layer($mounted[0], 'front_controller_fallback', $pathInfo);
    }

    /**
     * @param array<string, string> $routes
     * @param callable(string): bool $isStable
     */
    private static function matchFromReferer(Request $request, array $routes, callable $isStable, string $fixPath): ?AppLayer
    {
        $referer = (string) $request->headers->get('Referer', '');

        if ($referer === '') {
            return null;
        }

        $refererPath = parse_url($referer, PHP_URL_PATH);

        if (!is_string($refererPath) || $refererPath === '') {
            return null;
        }

        $match = AppRouteMatcher::match($refererPath, $routes, $isStable);

        if ($match === null || self::isWelcomeFallback($match['package'])) {
            return null;
        }

        return self::layer($match, 'front_controller_referer', $fixPath);
    }

    /**
     * @param array<string, string> $routes
     * @param callable(string): bool $isStable
     * @return list<array{path: string, package: string}>
     */
    private static function mountedApps(array $routes, callable $isStable): array
    {
        $mounted = [];

        foreach ($routes as $routePath => $package) {
            if (!is_string($routePath) || !is_string($package)) {
                continue;
            }

            if (in_array($routePath, ['*'], true)) {
                continue;
            }

            if (!$isStable($package) || self::isWelcomeFallback($package)) {
                continue;
            }

            $normalizedPath = AppRouteMatcher::normalize($routePath);
            $mounted[$normalizedPath] = [
                'path' => $normalizedPath,
                'package' => $package,
            ];
        }

        uksort($mounted, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return array_values($mounted);
    }

    private static function isWelcomeFallback(string $package): bool
    {
        return $package === 'com_pinoox_welcome';
    }

    /**
     * @param array{path: string, package: string} $match
     */
    private static function layer(array $match, string $matchedBy, string $fixPath): AppLayer
    {
        return new AppLayer(
            '/',
            $match['package'],
            [
                'matched_by' => $matchedBy,
                'web_server_fix' => WebServerFix::normalizePath($fixPath),
                'mount_path' => $match['path'],
            ],
        );
    }
}
