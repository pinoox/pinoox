<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Support\SystemConfig;

class PinxVersion
{
    /**
     * @return array{name: string, code: int|null}
     */
    public static function pinoox(): array
    {
        $name = '';
        $code = null;

        if (function_exists('config')) {
            try {
                $name = trim((string) config('~pinoox.version_name', ''));
                $rawCode = config('~pinoox.version_code', null);
                if ($rawCode !== null && $rawCode !== '') {
                    $code = (int) $rawCode;
                }
            } catch (\Throwable) {
            }
        }

        if ($name === '' && $code === null) {
            $configFile = rtrim((string) Loader::getBasePath(), '/\\') . '/pincore/config/pinoox.config.php';
            if (is_file($configFile)) {
                $config = include $configFile;
                if (is_array($config)) {
                    $name = trim((string) ($config['version_name'] ?? ''));
                    if (isset($config['version_code']) && $config['version_code'] !== '') {
                        $code = (int) $config['version_code'];
                    }
                }
            }
        }

        return [
            'name' => $name,
            'code' => $code,
        ];
    }

    public static function satisfiesMinpin(int $minpin): bool
    {
        if ($minpin <= 0) {
            return true;
        }

        $version = self::pinoox();

        return $version['code'] !== null && $version['code'] >= $minpin;
    }

    public static function minpinError(int $minpin): string
    {
        $version = self::pinoox();
        $current = $version['code'] ?? 'unknown';

        return sprintf(
            'This package requires Pinoox version code %d or higher (current: %s).',
            $minpin,
            (string) $current
        );
    }
}

