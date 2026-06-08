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

    /** Default runtime when APP_ENV is unset (secure-by-default). */
    public const DEFAULT = self::PRODUCTION;

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
     * @return array{mode: string, debug: bool, exception: bool}
     */
    public static function readGlobal(): array
    {
        try {
            $pinoox = \Pinoox\Portal\Config::name('~pinoox')->get();

            if (is_array($pinoox)) {
                return [
                    'mode' => self::normalize((string) ($pinoox['mode'] ?? self::DEFAULT)),
                    'debug' => (bool) ($pinoox['debug'] ?? false),
                    'exception' => (bool) ($pinoox['exception'] ?? true),
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
     * @return array{mode: string, debug: bool, exception: bool}
     */
    private static function globalConfigFromFiles(): array
    {
        $pinoox = SystemConfig::get('pinoox');

        return [
            'mode' => self::normalize((string) ($pinoox['mode'] ?? self::DEFAULT)),
            'debug' => (bool) ($pinoox['debug'] ?? false),
            'exception' => (bool) ($pinoox['exception'] ?? true),
        ];
    }

    public static function normalize(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return self::ALIASES[$mode] ?? $mode;
    }

    /**
     * Project runtime mode from root .env — APP_ENV or MODE (APP_ENV wins when both set).
     */
    public static function fromEnv(?string $default = null): string
    {
        $default ??= self::DEFAULT;

        $appEnv = SystemConfig::env('APP_ENV');

        if (is_string($appEnv) && $appEnv !== '') {
            return self::normalize($appEnv);
        }

        $mode = SystemConfig::env('MODE');

        if (is_string($mode) && $mode !== '') {
            return self::normalize($mode);
        }

        return self::normalize($default);
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

    /**
     * Default APP_DEBUG when not set in .env: false for production, true for all other modes.
     */
    public static function defaultDebugForMode(?string $mode = null): bool
    {
        $mode ??= self::fromEnv();

        return self::normalize($mode) !== self::PRODUCTION;
    }

    /**
     * Whether Pinoox Exception / boot-time error handler is active (PINOOX_EXCEPTION).
     */
    public static function bootDebugEnabled(): bool
    {
        return self::readGlobal()['exception'];
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

        return self::readGlobal()['debug'];
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
     * Requested database connection name for display (mysql, sqlite, …).
     *
     * Does not validate against config — use {@see DatabaseConfig::connectionName()} when connecting.
     */
    public function databaseConnection(?string $package = null): string
    {
        return \Pinoox\Component\Database\DatabaseConfig::requestedConnectionName();
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

