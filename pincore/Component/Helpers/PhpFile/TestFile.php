<?php

namespace Pinoox\Component\Helpers\PhpFile;

use Pinoox\Component\File;
use Pinoox\Support\SystemConfig;

class TestFile extends PhpFile
{
    public static function create(
        string $path,
        string $testName,
        ?string $package = null,
        bool $unit = false,
    ): bool {
        $stub = $unit ? 'app.tests.unit.stub' : 'app.tests.feature.stub';
        $title = self::titleFromName($testName);
        $package ??= self::detectPackage($path);

        $source = self::renderStub($stub, [
            '{{test_title}}' => $title,
            '{{package}}' => $package ?? 'com_my_app',
        ]);

        return File::generate($path, $source);
    }

    public static function scaffoldAppTests(string $package, string $appDir): void
    {
        $testsDir = rtrim($appDir, '/\\') . '/tests';
        $featureDir = $testsDir . '/Feature';
        $unitDir = $testsDir . '/Unit';

        foreach ([$featureDir, $unitDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $replacements = [
            '{{package}}' => $package,
            '{{package_segment}}' => $package,
        ];

        File::generate(
            $testsDir . '/Pest.php',
            self::renderStub('app.tests.pest.stub', $replacements),
        );

        File::generate(
            $featureDir . '/AppBootTest.php',
            self::renderStub('app.tests.feature.boot.stub', $replacements),
        );
    }

    private static function titleFromName(string $testName): string
    {
        $name = preg_replace('/Test$/', '', $testName) ?? $testName;
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name) ?? $name;

        return strtolower(trim($name));
    }

    private static function detectPackage(string $path): ?string
    {
        if (preg_match('#/apps/([^/]+)/tests/#', str_replace('\\', '/', $path), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param array<string, string> $replacements
     */
    private static function renderStub(string $stub, array $replacements): string
    {
        $content = file_get_contents(SystemConfig::path('stubs') . '/' . $stub);

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
