<?php

namespace Feature;

use PHPUnit\Framework\TestCase;
use Pinoox\Component\Kernel\Debug\Support\ExceptionContext;

class ExceptionContextTest extends TestCase
{
    public function test_pinoox_version_reads_from_project_config(): void
    {
        $version = ExceptionContext::pinooxVersion();

        $this->assertNotSame('', $version['name']);
        $this->assertNotNull($version['code']);
        $this->assertStringContainsString($version['name'], $version['label']);
        $this->assertStringContainsString('#' . $version['code'], $version['label']);
    }

    public function test_app_version_reads_from_installer_app_file(): void
    {
        $appFile = dirname(__DIR__, 3) . '/apps/com_pinoox_installer/app.php';
        $this->assertFileExists($appFile);

        $config = include $appFile;
        $this->assertIsArray($config);

        $version = ExceptionContext::appVersion('com_pinoox_installer');

        $this->assertSame((string) ($config['version-name'] ?? ''), $version['name']);
        $this->assertSame((int) ($config['version-code'] ?? 0), $version['code']);
        $this->assertSame($version['name'] . ' #' . $version['code'], $version['label']);
    }
}

