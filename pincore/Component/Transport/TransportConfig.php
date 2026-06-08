<?php

namespace Pinoox\Component\Transport;

use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\Platform;

/**
 * Resolves shared resources between apps.
 *
 * Granular keys (multi-word): user_table, auth_config, auth_cookie,
 * session_token, file_storage, access_table.
 *
 * Scenario presets (single-word): full, user, storage, access.
 *
 * Scope values: local | platform | host | {package}
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
     * App column values included in model global scope for a granular transport key.
     *
     * @return list<string>
     */
    public static function scopeValues(string $granularKey): array
    {
        $package = self::package($granularKey);

        if ($package !== self::PLATFORM) {
            return [$package];
        }

        $values = [self::PLATFORM];
        $authPackage = self::platformAuthPackage();

        if ($authPackage !== null) {
            $values[] = $authPackage;
        }

        return array_values(array_unique($values));
    }

    /**
     * App package that provides auth settings, or null when auth stays local.
     */
    public static function authSource(?string $hostPackage = null): ?string
    {
        $value = self::readAuthTransportValue();

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

        $transport = AppEngine::config($package)->get('transport');
        if (!is_array($transport)) {
            return $package;
        }

        $value = self::readAuthFromTransportBlock($transport);

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

    /**
     * @return array<string, string|null>
     */
    public static function resolved(): array
    {
        $resolved = [];

        foreach (TransportScenario::granularKeys() as $key) {
            $value = self::readTransportValue($key);
            $resolved[$key] = $value === null ? self::LOCAL : $value;
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    public static function activeScenarios(?array $transport = null): array
    {
        $transport ??= App::get('transport') ?? [];
        if (!is_array($transport)) {
            return [];
        }

        $active = [];
        foreach (TransportScenario::scenarioNames() as $scenario) {
            if (!empty($transport[$scenario])) {
                $active[] = $scenario;
            }
        }

        return $active;
    }

    private static function readAuthTransportValue(): ?string
    {
        $transport = App::get('transport');
        if (!is_array($transport)) {
            return null;
        }

        return self::readAuthFromTransportBlock($transport);
    }

    private static function readAuthFromTransportBlock(array $transport): ?string
    {
        foreach ([TransportScenario::AUTH_CONFIG, TransportScenario::AUTH_COOKIE] as $key) {
            $value = self::readFromTransportBlock($transport, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private static function readTransportValue(string $granularKey): ?string
    {
        $transport = App::get('transport');
        if (!is_array($transport)) {
            return null;
        }

        return self::readFromTransportBlock($transport, $granularKey);
    }

    private static function readFromTransportBlock(array $transport, string $granularKey): ?string
    {
        if (!empty($transport[$granularKey])) {
            return (string) $transport[$granularKey];
        }

        foreach (TransportScenario::scenarioNames() as $scenario) {
            if (empty($transport[$scenario])) {
                continue;
            }

            if (in_array($granularKey, TransportScenario::keysForScenario($scenario), true)) {
                return (string) $transport[$scenario];
            }
        }

        return null;
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
