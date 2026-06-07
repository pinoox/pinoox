<?php

namespace Pinoox\PinDoc;

use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Config;
use Pinoox\Portal\Url;

class DocsAppUrlResolver
{
    public static function resolve(string $package, array $docs, string $apiRelativeBase = ''): array
    {
        $explicit = trim((string)($docs['url'] ?? $docs['app_url'] ?? ''));
        $site = self::siteUrl();
        $appPath = self::appRoutePath($package);

        $apiRelativeBase = '/' . trim(str_replace('\\', '/', $apiRelativeBase), '/');
        $explicitUrl = $explicit;
        $appUrl = '';

        if ($explicitUrl !== '') {
            $appUrl = self::sanitizeUrl(self::normalizeUrl($explicitUrl, $site, $appPath));

            if ($site === '' && $appUrl !== '') {
                $site = preg_match('#^https?://#i', $appUrl) === 1
                    ? $appUrl
                    : $site;
            }
        } else {
            $appUrl = self::joinUrl($site, $appPath);
        }

        $apiRoot = $explicitUrl !== ''
            ? $appUrl
            : ($site !== '' ? $site : self::rootFromAppUrl($appUrl));
        $apiBaseUrl = self::joinUrl($apiRoot, $apiRelativeBase);

        return [
            'site_url' => $site,
            'app_url' => $explicitUrl !== '' ? $appUrl : '',
            'app_url_explicit' => $explicitUrl !== '',
            'api_base_url' => $apiBaseUrl,
        ];
    }

    public static function operationUrl(array $document, array $operation): string
    {
        $path = (string)($operation['path'] ?? '');

        if ($path === '') {
            return (string)($document['api_base_url'] ?? $document['baseUrl'] ?? '');
        }

        $site = trim((string)($document['site_url'] ?? ''));

        if ($site !== '') {
            return self::joinUrl($site, $path);
        }

        $appUrl = rtrim((string)($document['app_url'] ?? ''), '/');

        if ($appUrl !== '' && preg_match('#^https?://#i', $appUrl) === 1) {
            return self::joinUrl(self::rootFromAppUrl($appUrl), $path);
        }

        return $path;
    }

    private static function appRoutePath(string $package): string
    {
        try {
            $routes = AppRouter::getByPackage($package) ?? [];
        } catch (\Throwable) {
            return '/';
        }

        if ($routes === []) {
            return '/';
        }

        $paths = array_keys($routes);
        $paths = array_values(array_filter($paths, static fn(string $path): bool => $path !== '*'));
        usort($paths, static fn(string $a, string $b): int => strlen($a) <=> strlen($b));

        return $paths[0] ?? '/';
    }

    private static function siteUrl(): string
    {
        $candidates = [];

        try {
            $candidates[] = Url::origin();
        } catch (\Throwable) {
        }

        $candidates[] = self::domainFromConfig();

        try {
            $candidates[] = Url::base();
        } catch (\Throwable) {
        }

        foreach ($candidates as $candidate) {
            $candidate = self::sanitizeUrl((string)$candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private static function sanitizeUrl(string $url): string
    {
        $url = trim(str_replace('\\', '/', $url));

        if ($url === '' || $url === '/') {
            return '';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $parts = parse_url($url);
            $host = trim((string)($parts['host'] ?? ''));

            if ($host === '' || $host === ':') {
                return '';
            }

            return rtrim($url, '/');
        }

        return rtrim($url, '/');
    }

    private static function domainFromConfig(): string
    {
        try {
            $default = Config::name('~domain')->get('default');

            if (is_string($default) && trim($default) !== '') {
                return trim($default);
            }
        } catch (\Throwable) {
        }

        $env = $_ENV['PINOOX_DOMAIN'] ?? getenv('PINOOX_DOMAIN');

        return is_string($env) ? trim($env) : '';
    }

    private static function normalizeUrl(string $url, string $site, string $appPath): string
    {
        $url = str_replace('\\', '/', trim($url));

        if (preg_match('#^https?://#i', $url) === 1) {
            return self::sanitizeUrl($url);
        }

        if (str_starts_with($url, '/')) {
            return self::joinUrl($site, $url);
        }

        if ($site !== '') {
            return rtrim($site, '/') . '/' . ltrim($url, '/');
        }

        return self::joinUrl($appPath, $url);
    }

    private static function rootFromAppUrl(string $appUrl): string
    {
        if (preg_match('#^(https?://[^/]+)#i', $appUrl, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private static function joinUrl(string $base, string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $path = '/' . trim(str_replace('\\', '/', $path), '/');

        if ($path === '/') {
            return $base !== '' ? $base : '/';
        }

        if ($base === '') {
            return $path;
        }

        if (preg_match('#^https?://#i', $base) === 1 && str_starts_with($path, '//')) {
            return $base;
        }

        return $base . $path;
    }
}

