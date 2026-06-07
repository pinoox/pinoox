<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Support\SystemConfig;

final class PinxSignKey
{

    public const ALGORITHM = 'ed25519';

    public const KEY_FILE = 'sign.key.json';

    /**
     * @return array{
     *     key_id: string,
     *     algorithm: string,
     *     public_key: string,
     *     secret_key: string
     * }
     */
    public static function generate(string $package, ?string $keyId = null): array
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            throw new Exception('Ed25519 signing requires the PHP sodium extension.');
        }

        $keyPair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $secretKey = sodium_crypto_sign_secretkey($keyPair);

        return [
            'key_id' => $keyId ?: $package . ':main',
            'algorithm' => self::ALGORITHM,
            'public_key' => base64_encode($publicKey),
            'secret_key' => base64_encode($secretKey),
        ];
    }

    /**
     * @param array{key_id?: string, algorithm?: string, public_key: string, secret_key: string} $key
     */
    public static function save(array $key, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = [
            'key_id' => (string) ($key['key_id'] ?? 'main'),
            'algorithm' => self::ALGORITHM,
            'public_key' => (string) $key['public_key'],
            'secret_key' => (string) $key['secret_key'],
        ];

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{
     *     key_id: string,
     *     algorithm: string,
     *     public_key: string,
     *     secret_key: string
     * }
     */
    public static function load(string $path): array
    {
        if (!is_file($path)) {
            throw new Exception('Signing key not found: ' . $path);
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || empty($data['public_key']) || empty($data['secret_key'])) {
            throw new Exception('Invalid signing key file: ' . $path);
        }

        return [
            'key_id' => (string) ($data['key_id'] ?? 'main'),
            'algorithm' => (string) ($data['algorithm'] ?? self::ALGORITHM),
            'public_key' => (string) $data['public_key'],
            'secret_key' => (string) $data['secret_key'],
        ];
    }

    public static function defaultKeyPath(string $package, string $appPath): string
    {
        $local = rtrim($appPath, '/\\') . '/pinx/' . self::KEY_FILE;
        if (is_file($local)) {
            return $local;
        }

        $globalDir = SystemConfig::resolvePath(PinxSignConfig::system()['keys_path']);

        return rtrim($globalDir, '/\\') . '/' . $package . '.key.json';
    }

    public static function fingerprint(string $publicKeyBase64): string
    {
        $raw = base64_decode($publicKeyBase64, true);

        return substr(hash('sha256', $raw !== false ? $raw : $publicKeyBase64), 0, 16);
    }

    /**
     * @param array{public_key: string, secret_key: string} $key
     */
    public static function signMessage(string $message, array $key): string
    {
        if (!function_exists('sodium_crypto_sign_detached')) {
            throw new Exception('Ed25519 signing requires the PHP sodium extension.');
        }

        $secret = base64_decode($key['secret_key'], true);
        if ($secret === false) {
            throw new Exception('Invalid secret key encoding.');
        }

        return base64_encode(sodium_crypto_sign_detached($message, $secret));
    }

    public static function verifyMessage(string $message, string $signatureBase64, string $publicKeyBase64): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new Exception('Ed25519 verification requires the PHP sodium extension.');
        }

        $signature = base64_decode($signatureBase64, true);
        $publicKey = base64_decode($publicKeyBase64, true);

        if ($signature === false || $publicKey === false) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
    }
}

