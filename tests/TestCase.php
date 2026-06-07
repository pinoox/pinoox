<?php

namespace Tests;

use Pinoox\Component\Test\AppTestKit;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AppTestKit::boot();
    }

    protected function tearDown(): void
    {
        try {
            AppTestKit::cleanupTransientArtifacts();
        } catch (\Throwable) {
            // Keep teardown resilient so filesystem cleanup still runs on the next test.
        }

        parent::tearDown();
    }
}

