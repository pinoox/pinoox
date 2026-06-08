<?php

namespace Pinoox\Component\Package;

use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;

/**
 * App folder manifest (app.php).
 */
final class AppManifest
{
    public const FILE = 'app.php';

    /**
     * @return array<string, mixed>
     */
    public static function load(?string $package = null): array
    {
        $package = self::resolvePackage($package);

        if ($package === '' || !AppEnginePortal::exists($package)) {
            return [];
        }

        try {
            $data = AppEnginePortal::config($package)->all();
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    public static function get(?string $package, ?string $key = null, mixed $default = null): mixed
    {
        return ManifestConfig::get(self::load($package), $key, $default);
    }

    public static function package(?string $package = null): string
    {
        $config = self::load($package);

        return (string) ($config['package'] ?? self::resolvePackage($package));
    }

    public static function resolvePackage(?string $package): string
    {
        if (is_string($package) && $package !== '') {
            return $package;
        }

        try {
            $active = App::package();

            return is_string($active) && $active !== '' ? $active : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
