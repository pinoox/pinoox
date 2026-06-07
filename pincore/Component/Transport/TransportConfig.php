<?php

namespace Pinoox\Component\Transport;

use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\Platform;

/**
 * Resolves shared resources between apps (user, auth, token, file, access).
 *
 * Values in app.php → transport.*:
 * - local (default) — use the current app package
 * - platform        — shared platform scope / platform auth app
 * - host            — inherit from the app that opened this one (meeting / preview)
 * - {package}       — explicit app package name, e.g. com_pinoox_manager
 */
final class TransportConfig
{
    public const PLATFORM = Platform::PACKAGE;

    public const HOST = 'host';

    public const LOCAL = 'local';

    public static function package(string $key): string
    {
        return self::resolveScope(
            self::readTransportValue($key),
            App::package(),
            TransportContext::host(),
        );
    }

    /**
     * App package that provides auth settings, or null when auth stays local.
     */
    public static function authSource(?string $hostPackage = null): ?string
    {
        $value = self::readTransportValue('auth');

        if ($value === null) {
            return null;
        }

        return self::resolveAuthSource($value, $hostPackage ?? TransportContext::host());
    }

    public static function sharesAuthWith(string $guestPackage, string $hostPackage): bool
    {
        $guestSource = self::authSourceForPackage($guestPackage, $hostPackage);
        $hostSource = self::authSourceForPackage($hostPackage, $hostPackage);

        return $guestSource !== null
            && $hostSource !== null
            && $guestSource === $hostSource;
    }

    public static function authSourceForPackage(string $package, ?string $hostPackage = null): ?string
    {
        if (!AppEngine::exists($package)) {
            return null;
        }

        $value = AppEngine::config($package)->get('transport.auth');

        if ($value === null || $value === '' || $value === self::LOCAL) {
            return $package;
        }

        return self::resolveAuthSource((string) $value, $hostPackage);
    }

    public static function platformAuthPackage(): ?string
    {
        static $package = null;

        if ($package === null) {
            $package = AppEngine::exists('com_pinoox_manager')
                ? 'com_pinoox_manager'
                : false;
        }

        return $package === false ? null : $package;
    }

    private static function readTransportValue(string $key): ?string
    {
        $value = App::get('transport.' . $key);

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function resolveScope(?string $value, string $fallback, ?string $hostPackage): string
    {
        if ($value === null || $value === self::LOCAL) {
            return $fallback;
        }

        if ($value === self::HOST) {
            return ($hostPackage !== null && $hostPackage !== '') ? $hostPackage : $fallback;
        }

        if ($value === self::PLATFORM) {
            return self::PLATFORM;
        }

        return AppEngine::exists($value) ? $value : $fallback;
    }

    private static function resolveAuthSource(string $value, ?string $hostPackage): ?string
    {
        if ($value === self::HOST) {
            if ($hostPackage !== null && $hostPackage !== '' && AppEngine::exists($hostPackage)) {
                return $hostPackage;
            }

            return self::platformAuthPackage();
        }

        if ($value === self::PLATFORM) {
            return self::platformAuthPackage();
        }

        return AppEngine::exists($value) ? $value : null;
    }
}
