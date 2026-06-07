<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Kernel\Exception;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Prepare per-app Composer vendor for distributable packages.
 *
 * Production packages must ship require dependencies only (no require-dev).
 */
final class AppComposerVendor
{
    public static function composerJsonPath(string $appPath): string
    {
        return rtrim(str_replace('\\', '/', $appPath), '/') . '/composer.json';
    }

    public static function vendorPath(string $appPath): string
    {
        return rtrim(str_replace('\\', '/', $appPath), '/') . '/vendor';
    }

    public static function hasComposerJson(string $appPath): bool
    {
        return is_file(self::composerJsonPath($appPath));
    }

    /**
     * @return array{prepared: bool, reason: ?string}
     */
    public static function prepare(string $appPath, ?string $projectRoot = null): array
    {
        if (!self::hasComposerJson($appPath)) {
            return ['prepared' => false, 'reason' => 'composer.json not found'];
        }

        $projectRoot ??= self::detectProjectRoot($appPath);
        $command = self::buildInstallCommand($appPath, $projectRoot);

        $process = new Process(
            $command,
            $appPath,
            null,
            null,
            600,
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $output = trim($process->getErrorOutput() . "\n" . $process->getOutput());

            throw new Exception('Composer install failed for app package: ' . ($output !== '' ? $output : 'unknown error'));
        }

        if (!is_file(self::vendorPath($appPath) . '/autoload.php')) {
            throw new Exception('Composer install did not produce vendor/autoload.php');
        }

        return ['prepared' => true, 'reason' => null];
    }

    /**
     * @return list<string>
     */
    public static function buildInstallCommand(string $appPath, ?string $projectRoot = null): array
    {
        $composer = self::resolveComposerBinary($projectRoot);

        if (str_contains($composer, ' ') && str_ends_with($composer, '.phar')) {
            return array_merge(explode(' ', $composer, 2), [
                'install',
                '--no-dev',
                '--optimize-autoloader',
                '--no-interaction',
                '--no-progress',
            ]);
        }

        return [
            $composer,
            'install',
            '--no-dev',
            '--optimize-autoloader',
            '--no-interaction',
            '--no-progress',
        ];
    }

    public static function resolveComposerBinary(?string $projectRoot = null): string
    {
        $env = getenv('COMPOSER_BIN');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        if ($projectRoot !== null) {
            $localPhar = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/composer.phar';
            if (is_file($localPhar)) {
                return PHP_BINARY . ' ' . $localPhar;
            }
        }

        $finder = new ExecutableFinder();
        $binary = $finder->find('composer');
        if (is_string($binary) && $binary !== '') {
            return $binary;
        }

        return 'composer';
    }

    public static function detectProjectRoot(string $appPath): string
    {
        $dir = rtrim(str_replace('\\', '/', $appPath), '/');

        while ($dir !== '' && $dir !== '.' && $dir !== '/') {
            if (is_dir($dir . '/pincore') && is_dir($dir . '/apps')) {
                return $dir;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }

            $dir = $parent;
        }

        return dirname($dir, 2);
    }
}

