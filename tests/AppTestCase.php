<?php

namespace Tests;

use Pinoox\Component\Test\AppTestKit;

abstract class AppTestCase extends TestCase
{
    protected static ?string $package = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (static::$package !== null) {
            AppTestKit::setPackage(static::$package);
        } else {
            $detected = AppTestKit::detectPackageFromPath();
            if ($detected !== null) {
                AppTestKit::setPackage($detected);
            }
        }
    }
}

