<?php

namespace Pinoox\Component\Package\Pinx;

use PhpZip\ZipFile;
use Pinoox\Component\Kernel\Exception;

final class PinxSignature
{

    public const FILE = 'signature.json';

    public const FORMAT = 'pinx-signature';

    public const VERSION = 1;

    /**
     * @return array<string, string> entry => sha256 hex
     */
    public static function payloadHashes(ZipFile $zip): array
    {
        $hashes = [];

        foreach ($zip->getListFiles() as $entry) {
            if ($entry === PinxManifest::MANIFEST_FILE || $entry === self::FILE) {
                continue;
            }

            if (!str_starts_with($entry, PinxManifest::PAYLOAD_PREFIX)) {
                continue;
            }

            $hashes[$entry] = hash('sha256', $zip->getEntryContents($entry));
        }

        ksort($hashes);

        return $hashes;
    }

    public static function payloadDigest(array $payloadHashes): string
    {
        if ($payloadHashes === []) {
            throw new Exception('Cannot sign an empty payload.');
        }

        $lines = [];
        foreach ($payloadHashes as $entry => $hash) {
            $lines[] = $entry . ':' . $hash;
        }

        return hash('sha256', implode("\n", $lines));
    }

    public static function manifestDigest(string $manifestJson): string
    {
        return hash('sha256', $manifestJson);
    }

    public static function signingMessage(string $manifestDigest, string $payloadDigest): string
    {
        return self::FORMAT . ':' . self::VERSION . "\n" . $manifestDigest . "\n" . $payloadDigest;
    }

    /**
     * @param array{key_id: string, public_key: string, secret_key: string} $key
     * @return array<string, mixed>
     */
    public static function create(
        string $manifestJson,
        array $payloadHashes,
        array $key,
    ): array {
        $manifestDigest = self::manifestDigest($manifestJson);
        $payloadDigest = self::payloadDigest($payloadHashes);
        $message = self::signingMessage($manifestDigest, $payloadDigest);

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'algorithm' => PinxSignKey::ALGORITHM,
            'key_id' => $key['key_id'],
            'public_key' => $key['public_key'],
            'fingerprint' => PinxSignKey::fingerprint($key['public_key']),
            'manifest_sha256' => $manifestDigest,
            'payload_sha256' => $payloadDigest,
            'signature' => PinxSignKey::signMessage($message, $key),
            'signed_at' => gmdate('c'),
        ];
    }

    /**
     * @param array<string, mixed> $signature
     */
    public static function verify(string $manifestJson, array $payloadHashes, array $signature): void
    {
        if (($signature['format'] ?? '') !== self::FORMAT) {
            throw new Exception('Unsupported pinx signature format.');
        }

        if ((int) ($signature['version'] ?? 0) !== self::VERSION) {
            throw new Exception('Unsupported pinx signature version.');
        }

        $publicKey = (string) ($signature['public_key'] ?? '');
        $signatureValue = (string) ($signature['signature'] ?? '');

        if ($publicKey === '' || $signatureValue === '') {
            throw new Exception('Signature file is incomplete.');
        }

        $manifestDigest = self::manifestDigest($manifestJson);
        $payloadDigest = self::payloadDigest($payloadHashes);

        if (!hash_equals((string) ($signature['manifest_sha256'] ?? ''), $manifestDigest)) {
            throw new Exception('Manifest was modified after signing.');
        }

        if (!hash_equals((string) ($signature['payload_sha256'] ?? ''), $payloadDigest)) {
            throw new Exception('Package payload was modified after signing.');
        }

        $message = self::signingMessage($manifestDigest, $payloadDigest);

        if (!PinxSignKey::verifyMessage($message, $signatureValue, $publicKey)) {
            throw new Exception('Package signature verification failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromJson(string $json): array
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new Exception('Invalid signature.json');
        }

        return $data;
    }
}

