<?php

namespace Pinoox\Component\Runtime;

use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Support\SystemConfig;

final class RuntimeMode
{
    public const DEVELOPMENT = 'development';
    public const PRODUCTION = 'production';
    public const TEST = 'test';
    public const STAGING = 'staging';

    /** @var array<string, string> */
    private const ALIASES = [
        'dev' => self::DEVELOPMENT,
        'local' => self::DEVELOPMENT,
        'prod' => self::PRODUCTION,
        'testing' => self::TEST,
    ];

    public function __construct(
        private AppEngine $engine,
    ) {
    }

    /**
     * Read live global runtime settings (Config when booted, files otherwise).
     *
     * @return array{mode: string, debug: bool}
     */
    public static function readGlobal(): array
    {
        try {
            $pinoox = \Pinoox\Portal\Config::name('~pinoox')->get();

            if (is_array($pinoox)) {
                return [
                    'mode' => self::normalize((string) ($pinoox['mode'] ?? self::DEVELOPMENT)),
                    'debug' => (bool) ($pinoox['debug'] ?? false),
                ];
            }
        } catch (\Throwable) {
        }

        return self::globalConfigFromFiles();
    }

    /**
     * @return array{mode: string, debug: bool}
     */
    public static function globalConfig(): array
    {
        return self::readGlobal();
    }

    /**
     * @return array{mode: string, debug: bool}
     */
    private static function globalConfigFromFiles(): array
    {
        $pinoox = SystemConfig::get('pinoox');

        return [
            'mode' => self::normalize((string) ($pinoox['mode'] ?? self::DEVELOPMENT)),
            'debug' => (bool) ($pinoox['debug'] ?? false),
        ];
    }

    public static function normalize(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return self::ALIASES[$mode] ?? $mode;
    }

    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return [
            self::DEVELOPMENT,
            self::PRODUCTION,
            self::TEST,
            self::STAGING,
        ];
    }

    public static function bootDebugEnabled(): bool
    {
        $global = self::readGlobal();

        if ($global['debug']) {
            return true;
        }

        return in_array($global['mode'], [self::DEVELOPMENT, self::TEST], true);
    }

    public function name(?string $package = null): string
    {
        $runtime = $this->appRuntime($this->resolvePackage($package));

        if ($runtime['mode'] !== null) {
            return self::normalize($runtime['mode']);
        }

        return $this->globalMode();
    }

    private function globalMode(): string
    {
        return self::readGlobal()['mode'];
    }

    /**
     * Whether route action validation should run (development / debug).
     */
    public function shouldValidateActions(?string $package = null): bool
    {
        return $this->debug($package);
    }

    public function debug(?string $package = null): bool
    {
        $runtime = $this->appRuntime($this->resolvePackage($package));

        if ($runtime['debug'] !== null) {
            return (bool) $runtime['debug'];
        }

        $global = self::readGlobal();

        if ($global['debug']) {
            return true;
        }

        return match ($this->name($package)) {
            self::DEVELOPMENT, self::TEST => true,
            self::STAGING => false,
            default => false,
        };
    }

    public function is(string $mode, ?string $package = null): bool
    {
        return $this->name($package) === self::normalize($mode);
    }

    public function isProduction(?string $package = null): bool
    {
        return $this->name($package) === self::PRODUCTION;
    }

    public function isDevelopment(?string $package = null): bool
    {
        return $this->name($package) === self::DEVELOPMENT;
    }

    public function isTest(?string $package = null): bool
    {
        return $this->name($package) === self::TEST;
    }

    public function isStaging(?string $package = null): bool
    {
        return $this->name($package) === self::STAGING;
    }

    /**
     * Database profile key inside database.config.php for the active mode.
     */
    public function databaseConnection(?string $package = null): string
    {
        $mode = $this->name($package);
        $profiles = SystemConfig::get('database');

        if (!is_array($profiles)) {
            return self::TEST;
        }

        if (isset($profiles[$mode]) && is_array($profiles[$mode])) {
            return $mode;
        }

        return match ($mode) {
            self::PRODUCTION => isset($profiles[self::PRODUCTION]) ? self::PRODUCTION : 'development',
            self::STAGING => isset($profiles[self::STAGING]) ? self::STAGING : 'development',
            self::TEST => 'test',
            default => 'development',
        };
    }

    /**
     * Whether runtime cache should be enabled for this package.
     *
     * Opt-in only: set cache.enabled => true in app.php. Project mode alone does not enable it.
     */
    public function cacheEnabledByDefault(?string $package = null): bool
    {
        $package = $this->resolvePackage($package);

        if ($package !== null) {
            try {
                $cache = $this->engine->config($package)->get('cache');
                if (is_array($cache) && array_key_exists('enabled', $cache) && $cache['enabled'] !== null) {
                    return (bool) $cache['enabled'];
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }

    /**
     * Resolved cache behaviour mode (development vs production stores).
     */
    public function cacheMode(?string $package = null): string
    {
        $package = $this->resolvePackage($package);

        if ($package !== null) {
            try {
                $cache = $this->engine->config($package)->get('cache');
                if (is_array($cache) && !empty($cache['mode'])) {
                    $mode = self::normalize((string) $cache['mode']);

                    return in_array($mode, [self::PRODUCTION, self::DEVELOPMENT], true)
                        ? $mode
                        : ($this->isProduction($package) ? self::PRODUCTION : self::DEVELOPMENT);
                }
            } catch (\Throwable) {
            }
        }

        return $this->isProduction($package) || $this->isStaging($package)
            ? self::PRODUCTION
            : self::DEVELOPMENT;
    }

    public function defaultLogLevel(?string $package = null): string
    {
        return $this->isProduction($package) ? 'warning' : 'debug';
    }

    /**
     * @return array{
     *     mode: string,
     *     debug: bool,
     *     production: bool,
     *     database: string,
     *     cache_mode: string,
     *     cache_enabled: bool,
     *     package: string|null
     * }
     */
    public function profile(?string $package = null): array
    {
        $package = $this->resolvePackage($package);

        return [
            'mode' => $this->name($package),
            'debug' => $this->debug($package),
            'production' => $this->isProduction($package),
            'database' => $this->databaseConnection($package),
            'cache_mode' => $this->cacheMode($package),
            'cache_enabled' => $this->cacheEnabledByDefault($package),
            'package' => $package,
        ];
    }

    /** @deprecated Use name() */
    public function get(?string $package = null): string
    {
        return $this->name($package);
    }

    /**
     * @return array{mode: ?string, debug: ?bool}
     */
    private function appRuntime(?string $package): array
    {
        if ($package === null || $package === '') {
            return ['mode' => null, 'debug' => null];
        }

        try {
            if (!$this->engine->exists($package)) {
                return ['mode' => null, 'debug' => null];
            }

            $runtime = $this->engine->config($package)->get('runtime');
        } catch (\Throwable) {
            return ['mode' => null, 'debug' => null];
        }

        if (!is_array($runtime)) {
            return ['mode' => null, 'debug' => null];
        }

        return [
            'mode' => isset($runtime['mode']) && $runtime['mode'] !== ''
                ? (string) $runtime['mode']
                : null,
            'debug' => array_key_exists('debug', $runtime) && $runtime['debug'] !== null
                ? (bool) $runtime['debug']
                : null,
        ];
    }

    private function resolvePackage(?string $package): ?string
    {
        if ($package !== null && $package !== '') {
            return $package;
        }

        try {
            $active = App::package();

            return is_string($active) && $active !== '' ? $active : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
