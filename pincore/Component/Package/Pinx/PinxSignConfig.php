<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Support\SystemConfig;

final class PinxSignConfig
{
    /**
     * @return array{
     *     verify: bool,
     *     require_signature: bool,
     *     keys_path: string,
     *     trusted_keys: array<string, string>
     * }
     */
    public static function system(): array
    {
        $config = SystemConfig::get('pinx');

        if (!is_array($config)) {
            $config = [];
        }

        $trusted = is_array($config['trusted_keys'] ?? null) ? $config['trusted_keys'] : [];
        $normalizedTrusted = [];

        foreach ($trusted as $package => $publicKey) {
            if (is_string($package) && is_string($publicKey) && trim($publicKey) !== '') {
                $normalizedTrusted[$package] = trim($publicKey);
            }
        }

        return [
            'verify' => (bool) ($config['verify'] ?? true),
            'require_signature' => (bool) ($config['require_signature'] ?? false),
            'keys_path' => (string) ($config['keys_path'] ?? '~storage/pinx/keys'),
            'trusted_keys' => $normalizedTrusted,
        ];
    }

    /**
     * @param array<string, mixed> $pinxConfig
     * @return array{
     *     enabled: bool,
     *     require_signature: bool,
     *     key_path: ?string,
     *     key_id: ?string
     * }
     */
    public static function app(array $pinxConfig): array
    {
        $sign = is_array($pinxConfig['sign'] ?? null) ? $pinxConfig['sign'] : [];

        $enabled = (bool) ($sign['enabled'] ?? $pinxConfig['sign'] ?? false);
        $keyPath = $sign['key'] ?? $pinxConfig['sign_key'] ?? null;

        return [
            'enabled' => $enabled,
            'require_signature' => (bool) ($sign['require'] ?? $pinxConfig['require_signature'] ?? false),
            'key_path' => is_string($keyPath) && trim($keyPath) !== '' ? trim($keyPath) : null,
            'key_id' => isset($sign['key_id']) && is_string($sign['key_id']) ? trim($sign['key_id']) : null,
        ];
    }
}

