<?php

namespace Pinoox\Component\Template\Frontend;

use Pinoox\Portal\App\App;

class FrontendConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function forThemePath(string $themePath): array
    {
        $themePath = rtrim(str_replace('\\', '/', $themePath), '/');
        $file = $themePath . '/frontend.config.php';

        if (is_file($file)) {
            $config = require $file;

            return self::normalize(is_array($config) ? $config : [], $themePath);
        }

        return self::normalize([], $themePath);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function normalize(array $overrides, string $themePath): array
    {
        $detected = self::detectStack($themePath);

        $config = array_replace_recursive([
            'stack' => $detected,
            'entry' => self::defaultEntry($detected),
            'manifest' => 'dist/.vite/manifest.json',
            'pinoox' => 'pinoox',
            'mount' => '#app',
            'dev' => [
                'enabled' => (bool) _env('VITE_DEV', false),
                'url' => rtrim((string) _env('VITE_DEV_SERVER', 'http://127.0.0.1:5173'), '/'),
            ],
            'ssr' => [
                'enabled' => false,
                'mode' => 'hybrid',
                'strategy' => ThemeSsr::STRATEGY_AUTO,
                'fragment' => 'dist/ssr/app.html',
                'meta' => 'dist/ssr/meta.json',
                'server' => 'dist/server/entry-server.mjs',
                'fallback' => ThemeSsr::FALLBACK_CSR,
                'node' => null,
            ],
            'seo' => [
                'defaults' => [],
            ],
        ], $overrides);

        try {
            if (self::themePathBelongsToActiveApp($themePath)) {
                $appFrontend = App::get('frontend');
                if (is_array($appFrontend)) {
                    $appFrontend = self::filterNullValues($appFrontend);
                    $config = array_replace_recursive($config, $appFrontend);
                }
            }
        } catch (\Throwable) {
        }

        if (empty($config['stack'])) {
            $config['stack'] = $detected;
        }

        if (empty($config['entry'])) {
            $config['entry'] = self::defaultEntry((string) $config['stack']);
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private static function filterNullValues(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if ($value === null) {
                continue;
            }

            $filtered[$key] = is_array($value) ? self::filterNullValues($value) : $value;
        }

        return $filtered;
    }

    public static function detectStack(string $themePath): string
    {
        $packageFile = $themePath . '/package.json';
        if (!is_file($packageFile)) {
            return 'twig';
        }

        $package = json_decode((string) file_get_contents($packageFile), true);
        if (!is_array($package)) {
            return 'twig';
        }

        $deps = array_merge($package['dependencies'] ?? [], $package['devDependencies'] ?? []);

        if (isset($deps['nuxt'])) {
            return 'nuxt';
        }

        if (isset($deps['next'])) {
            return 'next';
        }

        if (isset($deps['react']) || isset($deps['react-dom'])) {
            return 'react';
        }

        if (isset($deps['vue'])) {
            return 'vue';
        }

        if (isset($deps['vite'])) {
            return 'vite';
        }

        return 'twig';
    }

    private static function themePathBelongsToActiveApp(string $themePath): bool
    {
        try {
            $appThemeRoot = rtrim(str_replace('\\', '/', App::path('theme')), '/');
            $themePath = rtrim(str_replace('\\', '/', $themePath), '/');

            return $appThemeRoot !== '' && str_starts_with($themePath, $appThemeRoot);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function defaultEntry(string $stack): string
    {
        return match ($stack) {
            'react' => 'src/main.jsx',
            'next' => 'src/app/page.tsx',
            'nuxt' => 'src/main.js',
            default => 'src/main.js',
        };
    }

    public static function isDevEnabled(array $config): bool
    {
        return !empty($config['dev']['enabled']);
    }

    public static function isSsrEnabled(array $config): bool
    {
        return ThemeSsr::isEnabled($config);
    }
}

