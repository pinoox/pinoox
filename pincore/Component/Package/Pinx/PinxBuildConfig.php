<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Template\Theme\ThemeManifest;

class PinxBuildConfig
{
    /**
     * @return array{
     *     type: string,
     *     target_app: string,
     *     theme_name: string,
     *     minpin: int,
     *     gitignore: bool,
     *     exclude: list<string>,
     *     include_themes: list<string>,
     *     composer: bool,
     *     sign: array{enabled: bool, require_signature: bool, key_path: ?string, key_id: ?string}
     * }
     */
    public static function resolve(AppEngine $engine, string $package): array
    {
        $raw = self::rawAppConfig($engine, $package);
        $config = $engine->config($package);
        $pinx = is_array($raw['pinx'] ?? null) ? $raw['pinx'] : self::arrayValue($config, 'pinx');
        $build = is_array($raw['build'] ?? null) ? $raw['build'] : self::arrayValue($config, 'build');
        $sign = PinxSignConfig::app($pinx);

        $type = (string) ($pinx['type'] ?? PinxManifest::TYPE_APP);
        $packageName = (string) ($raw['package'] ?? $config->get('package', $package));
        $pathTheme = (string) ($raw['path-theme'] ?? $config->get('path-theme', 'theme'));
        $themeName = (string) ($pinx['theme_name'] ?? $raw['theme'] ?? $config->get('theme', 'default'));

        if ($type === PinxManifest::TYPE_THEME) {
            $themePath = rtrim(str_replace('\\', '/', $engine->path($packageName, $pathTheme . '/' . $themeName)), '/');
            $manifestFile = $themePath . '/' . ThemeManifest::FILE;

            if (!is_file($manifestFile)) {
                throw new \Pinoox\Component\Kernel\Exception(
                    'Theme pinx build requires theme.php at ' . $pathTheme . '/' . $themeName . '/theme.php',
                );
            }

            $themeManifest = ThemeManifest::fromPath($themePath, $packageName, $themeName);
            $themeManifest?->validate($packageName);
        }

        $exclude = self::stringList($build['exclude'] ?? []);
        $exclude = array_values(array_unique(array_merge($exclude, [
            'pinx/sign.key.json',
            '.pinx',
            '.pinx/*',
        ])));

        return [
            'type' => in_array($type, [PinxManifest::TYPE_APP, PinxManifest::TYPE_THEME], true)
                ? $type
                : PinxManifest::TYPE_APP,
            'target_app' => (string) ($pinx['target_app'] ?? $packageName),
            'theme_name' => $themeName,
            'minpin' => (int) ($pinx['minpin'] ?? $raw['minpin'] ?? $config->get('minpin', 0)),
            'gitignore' => (bool) ($build['gitignore'] ?? true),
            'exclude' => $exclude,
            'include_themes' => self::stringList($build['include_themes'] ?? []),
            'composer' => array_key_exists('composer', $build)
                ? (bool) $build['composer']
                : true,
            'sign' => $sign,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function appConfigArray(AppEngine $engine, string $package): array
    {
        return self::rawAppConfig($engine, $package);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rawAppConfig(AppEngine $engine, string $package): array
    {
        $appFile = $engine->path($package, 'app.php');
        if (!is_file($appFile)) {
            return [];
        }

        $data = include $appFile;

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(ConfigInterface $config, string $key): array
    {
        $value = $config->get($key, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $value)));
    }
}

