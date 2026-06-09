<?php

namespace Pinoox\Component\Helpers;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

/**
 * Load project .env before Portal __register hooks touch env-backed config.
 *
 * Secure-by-default: production → APP_DEBUG=false; other modes → true unless set in .env.
 * PINOOX_EXCEPTION defaults to true in all modes unless set in .env.
 * Root mode: APP_ENV and MODE are aliases (APP_ENV wins when both are set).
 */
final class EnvBootstrap
{
    private static bool $loaded = false;

    /** @var list<string> */
    private const PROD_ENVS = ['prod', 'production', 'staging'];

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        $envPath = rtrim(str_replace('\\', '/', $basePath), '/') . '/.env';

        try {
            $dotenv = (new Dotenv())->setProdEnvs(self::PROD_ENVS);
            $dotenv->bootEnv($envPath, RuntimeMode::PRODUCTION);

            $loadedKeys = self::loadedDotenvKeys();

            unset($_SERVER['SYMFONY_DOTENV_VARS'], $_ENV['SYMFONY_DOTENV_VARS']);

            self::applySecureDefaults($loadedKeys, $basePath);
        } catch (PathException) {
            self::applySecureDefaults([], $basePath);
        }
    }

    public static function loaded(): bool
    {
        return self::$loaded;
    }

    /** @internal Test helper */
    public static function reset(): void
    {
        self::$loaded = false;
    }

    /**
     * @return array<string, true>
     */
    private static function loadedDotenvKeys(): array
    {
        $raw = (string) ($_ENV['SYMFONY_DOTENV_VARS'] ?? $_SERVER['SYMFONY_DOTENV_VARS'] ?? '');

        return array_fill_keys(array_filter(explode(',', $raw)), true);
    }

    /**
     * @param array<string, true> $loadedFromDotenv Keys present in .env layer files
     */
    private static function applySecureDefaults(array $loadedFromDotenv, string $basePath): void
    {
        self::reconcileModeEnvKeys($basePath);

        if (!isset($loadedFromDotenv['APP_DEBUG']) && !self::envKeyDefinedInFiles($basePath, 'APP_DEBUG')) {
            self::setEnv('APP_DEBUG', RuntimeMode::defaultDebugForMode() ? 'true' : 'false');
        }

        if (!isset($loadedFromDotenv['PINOOX_EXCEPTION']) && !self::envKeyDefinedInFiles($basePath, 'PINOOX_EXCEPTION')) {
            self::setEnv('PINOOX_EXCEPTION', 'true');
        }
    }

    private static function reconcileModeEnvKeys(string $basePath): void
    {
        $appEnvExplicit = self::envKeyDefinedInFiles($basePath, 'APP_ENV');
        $modeExplicit = self::envKeyDefinedInFiles($basePath, 'MODE');

        if ($appEnvExplicit) {
            $normalized = RuntimeMode::normalize((string) SystemConfig::env('APP_ENV'));
        } elseif ($modeExplicit) {
            $normalized = RuntimeMode::normalize((string) SystemConfig::env('MODE'));
        } elseif (self::envPresent('APP_ENV')) {
            $normalized = RuntimeMode::normalize((string) SystemConfig::env('APP_ENV'));
        } elseif (self::envPresent('MODE')) {
            $normalized = RuntimeMode::normalize((string) SystemConfig::env('MODE'));
        } else {
            $normalized = RuntimeMode::PRODUCTION;
        }

        self::setEnv('APP_ENV', $normalized);
        self::setEnv('MODE', $normalized);
    }

    private static function envKeyDefinedInFiles(string $basePath, string $key): bool
    {
        $root = rtrim(str_replace('\\', '/', $basePath), '/');
        $candidates = [$root . '/.env'];

        if (is_file($root . '/.env.local')) {
            $candidates[] = $root . '/.env.local';
        }

        $pattern = '/^\s*' . preg_quote($key, '/') . '\s*=/m';

        foreach ($candidates as $file) {
            if (!is_file($file)) {
                continue;
            }

            $contents = @file_get_contents($file);

            if (!is_string($contents) || $contents === '') {
                continue;
            }

            if (preg_match($pattern, $contents) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function envPresent(string $key): bool
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value !== false && $value !== null && $value !== '';
    }

    private static function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}
