<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Package\AppComposerVendor;
use Pinoox\Component\Package\Engine\AppEngine;
use ZipArchive;

class PinxBuilder
{
    public function __construct(
        private AppEngine $engine,
        private PinxFileSelector $selector = new PinxFileSelector(),
    ) {
    }

    /**
     * @param array{
     *     sign?: bool,
     *     sign_key?: ?string,
     *     key_id?: ?string
     * } $options
     * @return array{path: string, manifest: PinxManifest, files: int, signed: bool, signature: ?array, composer: bool}
     */
    public function build(string $package, ?string $outputPath = null, array $options = []): array
    {
        if (!$this->engine->exists($package)) {
            throw new Exception('Package not found: ' . $package);
        }

        $build = PinxBuildConfig::resolve($this->engine, $package);
        $appConfig = PinxBuildConfig::appConfigArray($this->engine, $package);
        $manifest = PinxManifest::fromAppConfig($appConfig, $build['type'], [
            'target_app' => $build['target_app'],
            'theme_name' => $build['theme_name'],
            'minpin' => $build['minpin'],
        ]);

        $packagePath = $this->engine->path($package);
        $sourcePath = $build['type'] === PinxManifest::TYPE_THEME
            ? $packagePath . '/theme/' . $build['theme_name']
            : $packagePath;

        if (!is_dir($sourcePath)) {
            throw new Exception('Build source path not found: ' . $sourcePath);
        }

        $composerPrepared = false;
        $alwaysInclude = [];

        if ($build['type'] === PinxManifest::TYPE_APP && $build['composer'] && AppComposerVendor::hasComposerJson($packagePath)) {
            $composerPrepared = AppComposerVendor::prepare($packagePath)['prepared'];
            if ($composerPrepared) {
                $alwaysInclude[] = 'vendor';
            }
        }

        $buildConfig = [
            'gitignore' => $build['gitignore'],
            'exclude' => $build['exclude'],
            'include_themes' => $build['type'] === PinxManifest::TYPE_APP ? $build['include_themes'] : [],
            'always_include' => $alwaysInclude,
        ];

        if ($build['type'] === PinxManifest::TYPE_APP) {
            $buildConfig['exclude'] = array_values(array_unique(array_merge(
                $buildConfig['exclude'],
                ['export', 'export/*']
            )));
        }

        $payloadFiles = $this->selector->payloadFiles($sourcePath, $buildConfig);
        $fileCount = count($payloadFiles);

        if ($fileCount === 0) {
            throw new Exception('No files selected for pinx build.');
        }

        $outputPath ??= $this->defaultOutputPath($package, $manifest);
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create pinx archive: ' . $outputPath);
        }

        $manifestJson = $manifest->toJson();
        $zip->addFromString(PinxManifest::MANIFEST_FILE, $manifestJson);

        /** @var array<string, string> $payloadHashes */
        $payloadHashes = [];
        foreach ($payloadFiles as $relativePath => $realPath) {
            $entry = PinxManifest::PAYLOAD_PREFIX . $this->payloadEntry($build['type'], $build['theme_name'], $relativePath);
            $payloadHashes[$entry] = hash('sha256', (string) file_get_contents($realPath));
            $zip->addFile($realPath, $entry);
        }

        $signature = null;
        $signed = false;
        if ($this->shouldSign($package, $packagePath, $build, $options)) {
            $key = $this->resolveSigningKey($package, $packagePath, $build, $options);

            if (!empty($options['key_id'])) {
                $key['key_id'] = (string) $options['key_id'];
            } elseif (!empty($build['sign']['key_id'])) {
                $key['key_id'] = (string) $build['sign']['key_id'];
            } elseif ($key['key_id'] === 'main') {
                $key['key_id'] = $package . ':main';
            }

            $signature = PinxSignature::create($manifestJson, $payloadHashes, $key);
            $zip->addFromString(
                PinxSignature::FILE,
                json_encode($signature, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            );
            $signed = true;
        }

        $zip->close();

        return [
            'path' => $outputPath,
            'manifest' => $manifest,
            'files' => $fileCount,
            'signed' => $signed,
            'signature' => $signature,
            'composer' => $composerPrepared,
        ];
    }

    /**
     * @param array{sign: array{enabled: bool, require_signature: bool, key_path: ?string, key_id: ?string}} $build
     * @param array{sign?: bool, sign_key?: ?string} $options
     */
    private function shouldSign(string $package, string $packagePath, array $build, array $options): bool
    {
        if (array_key_exists('sign', $options)) {
            return (bool) $options['sign'];
        }

        if ($build['sign']['enabled']) {
            return true;
        }

        return is_file(PinxSignKey::defaultKeyPath($package, $packagePath));
    }

    /**
     * @param array{sign: array{enabled: bool, require_signature: bool, key_path: ?string, key_id: ?string}} $build
     * @param array{sign?: bool, sign_key?: ?string, key_id?: ?string} $options
     * @return array{key_id: string, algorithm: string, public_key: string, secret_key: string}
     */
    private function resolveSigningKey(string $package, string $packagePath, array $build, array $options): array
    {
        $path = $options['sign_key'] ?? $build['sign']['key_path'] ?? PinxSignKey::defaultKeyPath($package, $packagePath);

        if (!is_string($path) || !is_file($path)) {
            throw new Exception('Signing key not found. Run pinx:sign-keygen or pass --sign-key.');
        }

        $key = PinxSignKey::load($path);
        if (($key['algorithm'] ?? '') !== PinxSignKey::ALGORITHM) {
            throw new Exception('Unsupported signing algorithm: ' . ($key['algorithm'] ?? 'unknown'));
        }

        return $key;
    }

    private function payloadEntry(string $type, string $themeName, string $relativePath): string
    {
        if ($type === PinxManifest::TYPE_THEME) {
            return 'theme/' . $themeName . '/' . ltrim($relativePath, '/');
        }

        return ltrim($relativePath, '/');
    }

    private function defaultOutputPath(string $package, PinxManifest $manifest): string
    {
        $exportDir = $this->engine->path($package, 'export');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $suffix = $manifest->isTheme()
            ? $manifest->themeName() . '_theme'
            : $package;

        return $exportDir . '/' . $suffix . '_v' . $manifest->versionCode() . '_' . date('Ymd_His') . '.pinx';
    }
}

