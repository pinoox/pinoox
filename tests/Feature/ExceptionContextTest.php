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
        $version = ExceptionContext::appVersion('com_pinoox_installer');

        $this->assertSame('2.0', $version['name']);
        $this->assertSame(7, $version['code']);
        $this->assertSame('2.0 #7', $version['label']);
    }
}

