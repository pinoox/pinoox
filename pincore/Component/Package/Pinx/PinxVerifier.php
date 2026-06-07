<?php

namespace Pinoox\Component\Package\Pinx;

use PhpZip\ZipFile;
use Pinoox\Component\Kernel\Exception;

final class PinxVerifier
{
    /**
     * @param array{
     *     skip_verify?: bool,
     *     require_signature?: bool
     * } $options
     */
    public static function verify(
        ZipFile $zip,
        PinxManifest $manifest,
        string $manifestJson,
        array $options = [],
    ): ?array {
        if ($manifest->isLegacy()) {
            if (!empty($options['require_signature'])) {
                throw new Exception('Legacy .pin packages cannot satisfy signature requirements.');
            }

            return null;
        }

        $system = PinxSignConfig::system();

        if (!empty($options['skip_verify']) || !$system['verify']) {
            return self::readSignatureIfPresent($zip);
        }

        $hasSignature = $zip->hasEntry(PinxSignature::FILE);

        if (!$hasSignature) {
            if (!empty($options['require_signature'])) {
                throw new Exception('Package is not signed.');
            }

            return null;
        }

        $signature = PinxSignature::fromJson($zip->getEntryContents(PinxSignature::FILE));
        $payloadHashes = PinxSignature::payloadHashes($zip);
        PinxSignature::verify($manifestJson, $payloadHashes, $signature);

        self::assertTrustedPublisher($manifest, $signature, $system['trusted_keys']);

        return $signature;
    }

    /**
     * @param array<string, string> $trustedKeys
     * @param array<string, mixed> $signature
     */
    private static function assertTrustedPublisher(PinxManifest $manifest, array $signature, array $trustedKeys): void
    {
        if ($trustedKeys === []) {
            return;
        }

        $package = $manifest->package();
        if (!isset($trustedKeys[$package])) {
            return;
        }

        $expected = $trustedKeys[$package];
        $actual = (string) ($signature['public_key'] ?? '');

        if (!hash_equals($expected, $actual)) {
            throw new Exception('Package publisher is not in trusted keys for ' . $package . '.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function readSignatureIfPresent(ZipFile $zip): ?array
    {
        if (!$zip->hasEntry(PinxSignature::FILE)) {
            return null;
        }

        return PinxSignature::fromJson($zip->getEntryContents(PinxSignature::FILE));
    }
}

