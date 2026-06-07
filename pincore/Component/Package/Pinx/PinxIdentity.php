<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Exception;

final class PinxIdentity
{

    public const FILE = '.pinx/identity.json';

    /**
     * @return array<string, mixed>|null
     */
    public static function read(string $appPath): ?array
    {
        $file = rtrim($appPath, '/\\') . '/' . self::FILE;

        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $signature
     * @param array<string, mixed> $manifest
     */
    public static function write(string $appPath, array $signature, array $manifest): void
    {
        $dir = rtrim($appPath, '/\\') . '/.pinx';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = [
            'package' => (string) ($manifest['package'] ?? ''),
            'type' => (string) ($manifest['type'] ?? PinxManifest::TYPE_APP),
            'key_id' => (string) ($signature['key_id'] ?? ''),
            'public_key' => (string) ($signature['public_key'] ?? ''),
            'fingerprint' => (string) ($signature['fingerprint'] ?? ''),
            'first_signed_at' => (string) ($signature['signed_at'] ?? gmdate('c')),
            'last_verified_at' => gmdate('c'),
        ];

        $existing = self::read($appPath);
        if (is_array($existing) && !empty($existing['first_signed_at'])) {
            $payload['first_signed_at'] = (string) $existing['first_signed_at'];
        }

        file_put_contents(
            $dir . '/identity.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * @param array<string, mixed> $signature
     */
    public static function assertUpdateAllowed(string $appPath, PinxManifest $manifest, array $signature): void
    {
        $identity = self::read($appPath);

        if ($identity === null) {
            return;
        }

        $package = (string) ($identity['package'] ?? '');
        if ($package !== '' && $package !== $manifest->package()) {
            throw new Exception('Package identity mismatch: expected "' . $package . '".');
        }

        $expectedFingerprint = (string) ($identity['fingerprint'] ?? '');
        $actualFingerprint = (string) ($signature['fingerprint'] ?? PinxSignKey::fingerprint((string) ($signature['public_key'] ?? '')));

        if ($expectedFingerprint !== '' && !hash_equals($expectedFingerprint, $actualFingerprint)) {
            throw new Exception(
                'Update rejected: package signing key does not match the originally installed publisher.',
            );
        }
    }
}

